<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Seat
 * manage Seat entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Seat extends Model
{
    use HasUlids;

    protected $table = 'seat';
    protected $primaryKey = 'seat_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'seat_id',
        'zone_id',
        'row',
        'number',
        'status',
    ];

    protected $casts = ['number' => 'integer'];

    public function lockSeat(): void
    {
        $this->update(['status' => 'LOCKED']);
    }

    public function unlockSeat(): void
    {
        $this->update(['status' => 'AVAILABLE']);
    }

    public function confirmSeat(): void
    {
        $this->update(['status' => 'BOOKED']);
    }

    // ─── Relationships ───────────────────────────────────────

    /**
     * Seat belongs to a Zone
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_id', 'zone_id');
    }

    /**
     * Seat has many BookingSeat (pivot)
     */
    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class, 'seat_id', 'seat_id');
    }

    /**
     * Seat has one Ticket (nullable)
     */
    public function ticket(): HasOne
    {
        return $this->hasOne(Ticket::class, 'seat_id', 'seat_id');
    }

    /**
     * Seat has many SeatLockLog
     */
    public function seatLockLogs(): HasMany
    {
        return $this->hasMany(SeatLockLog::class, 'seat_id', 'seat_id');
    }
}
