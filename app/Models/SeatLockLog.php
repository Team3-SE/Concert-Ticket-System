<?php

/**
 * SeatLockLog
 * ประวัติการล็อกที่นั่ง (ตาราง seat_lock_log)
 * Author :
 * Created :
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeatLockLog extends Model
{
    protected $table = 'seat_lock_log';
    protected $primaryKey = 'lock_id';
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
     * booking()
     * ความสัมพันธ์กับการจอง (อาจเป็น null)
     * param : -
     * return : BelongsTo Booking
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    /**
     * seat()
     * ความสัมพันธ์กับที่นั่ง
     * param : -
     * return : BelongsTo Seat
     */
    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class, 'seat_id', 'seat_id');
    }
}
