<?php

/**
 * ZoneSeatPattern
 * รูปแบบการจัดที่นั่งของโซน (ตาราง zone_seat_pattern)
 * Author :
 * Created :
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ZoneSeatPattern extends Model
{
    protected $table = 'zone_seat_pattern';
    protected $primaryKey = 'pattern_id';
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

    /**
     * zone()
     * ความสัมพันธ์กับโซน
     * param : -
     * return : BelongsTo Zone
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_id', 'zone_id');
    }

    /**
     * seats()
     * ที่นั่งในโซนของรูปแบบนี้
     * param : -
     * return : HasMany Seat
     */
    public function seats(): HasMany
    {
        return $this->zone->seats();
    }

    // TODO: Create methods generateSeats
}
