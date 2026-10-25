<?php

/**
 * BookingSeat
 * ที่นั่งในแต่ละการจอง (ตาราง booking_seat)
 * Author :
 * Created :
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingSeat extends Model
{
    protected $table = 'booking_seat';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'booking_id', 'seat_id', 'zone_name_snapshot',
        'seat_label_snapshot', 'unit_price_snapshot',
    ];

    protected $casts = ['unit_price_snapshot' => 'decimal:2'];

    /**
     * booking()
     * ความสัมพันธ์กับการจอง
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
