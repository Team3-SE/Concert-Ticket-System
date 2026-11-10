<?php

/**
 * BookingSeat
 * ที่นั่งในแต่ละการจอง (ตาราง booking_seat)
 * schema 3.4.2: ชื่อโซนและราคาเก็บที่ booking แล้ว ตารางนี้เหลือแค่ชื่อที่นั่ง ณ เวลาที่จอง
 * Author :
 * Created :
 * Updated : 10/11/2569 67160292 ธีรเมธ พลสาย
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
        'bs_booking_id',
        'bs_seat_id',
        'seat_label_snapshot',
    ];

    /**
     * booking()
     * ความสัมพันธ์กับการจอง
     * param : -
     * return : BelongsTo Booking
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'bs_booking_id', 'b_id');
    }

    /**
     * seat()
     * ความสัมพันธ์กับที่นั่ง
     * param : -
     * return : BelongsTo Seat
     */
    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class, 'bs_seat_id', 's_id');
    }
}
