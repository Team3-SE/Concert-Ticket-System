<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ZoneSeatPattern extends Model
{
    use HasUlids;

    protected $table = 'zone_seat_pattern';
    protected $primaryKey = 'pattern_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'pattern_id', 'zone_id', 'pattern_type', 'start_row',
        'start_seat_number', 'row_count', 'seats_per_row',
    ];

    protected $casts = [
        'start_seat_number' => 'integer',
        'row_count' => 'integer',
        'seats_per_row' => 'integer',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_id', 'zone_id');
    }

    public function seats(): HasMany
    {
        return $this->zone->seats();
    }

    public function generateSeats(): array
    {
        $seats = [];
        for ($rowOffset = 0; $rowOffset < $this->row_count; $rowOffset++) {
            $row = $this->rowLabel($this->start_row, $rowOffset);
            for ($seatOffset = 0; $seatOffset < $this->seats_per_row; $seatOffset++) {
                $seats[] = new Seat([
                    'zone_id' => $this->zone_id,
                    'row' => $row,
                    'number' => $this->start_seat_number + $seatOffset,
                    'status' => 'AVAILABLE',
                ]);
            }
        }
        return $seats;
    }

    private function rowLabel(string $startRow, int $offset): string
    {
        $row = strtoupper($startRow);
        $value = 0;
        for ($index = 0; $index < strlen($row); $index++) {
            $value = ($value * 26) + ord($row[$index]) - 64;
        }
        $value += $offset;
        $label = '';
        while ($value > 0) {
            $value--;
            $label = chr(65 + ($value % 26)) . $label;
            $value = intdiv($value, 26);
        }
        return $label;
    }
}
