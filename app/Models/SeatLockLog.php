<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SeatLockLog
 * manage SeatLockLog entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class SeatLockLog extends Model
{
    use HasUlids;

    protected $table = 'seat_lock_log';
    protected $primaryKey = 'lock_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'lock_id',
        'booking_id',
        'seat_id',
        'locked_at',
        'expired_at',
        'released_at',
        'status',
    ];

    protected $casts = [
        'locked_at'   => 'datetime',
        'expired_at'  => 'datetime',
        'released_at' => 'datetime',
    ];

    public const STATUS_LOCKED = 'LOCKED';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_RELEASED = 'RELEASED';
    public const STATUS_CONVERTED_TO_BOOKING = 'CONVERTED_TO_BOOKING';

    // ─── Relationships ───────────────────────────────────────

    /**
     * SeatLockLog belongs to a Booking (nullable)
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    /**
     * SeatLockLog belongs to a Seat
     */
    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class, 'seat_id', 'seat_id');
    }
}
