<?php

/**
 * Seat
 * ที่นั่ง (ตาราง seat)
 * Author :
 * Created :
 * Updated : 10/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Seat extends Model
{
    protected $table = 'seat';
    protected $primaryKey = 's_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    // Booking Status Enumerations (seat.seat_booking_status ตัวพิมพ์เล็กตาม schema 3.4.2)
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_LOCKED    = 'locked';
    public const STATUS_RESERVED  = 'reserved';
    public const STATUS_SOLD      = 'sold';

    // Seat Status Enumerations (seat.seat_status สถานะตัวที่นั่งเอง ไม่เกี่ยวกับการจอง)
    public const SEAT_ACTIVE   = 'active';
    public const SEAT_DISABLED = 'disabled';
    public const SEAT_BLOCKED  = 'blocked';

    protected $fillable = [
        's_zone_id',
        'row',
        'number',
        'seat_booking_status',
        'seat_status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'number'     => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // Methods from Specification
    // ==========================================

    /**
     * label()
     * ชื่อที่นั่งสำหรับแสดงผล (แถว + เลขที่นั่ง เช่น A1)
     * param : -
     * return : string
     */
    public function label(): string
    {
        return $this->row . $this->number;
    }

    /**
     * lockSeat()
     * ล็อกที่นั่งชั่วคราวระหว่างการจอง (locked)
     * param : -
     * return : void
     */
    public function lockSeat(): void
    {
        $this->update(['seat_booking_status' => self::STATUS_LOCKED]);
    }

    /**
     * unlockSeat()
     * ปลดล็อกที่นั่งกลับเป็น available เมื่อยกเลิกหรือหมดเวลา
     * param : -
     * return : void
     */
    public function unlockSeat(): void
    {
        $this->update(['seat_booking_status' => self::STATUS_AVAILABLE]);
    }

    /**
     * confirmSeat()
     * ยืนยันที่นั่งหลังชำระเงินสำเร็จ (sold)
     * param : -
     * return : void
     */
    public function confirmSeat(): void
    {
        $this->update(['seat_booking_status' => self::STATUS_SOLD]);
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * zone()
     * ความสัมพันธ์กับโซนของที่นั่ง
     * param : -
     * return : BelongsTo Zone
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 's_zone_id', 'z_id');
    }

    /**
     * bookingSeats()
     * ความสัมพันธ์กับรายการจองของที่นั่ง
     * param : -
     * return : HasMany BookingSeat
     */
    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class, 'bs_seat_id', 's_id');
    }

    /**
     * ticket()
     * ความสัมพันธ์กับบัตรที่ยังใช้ได้ (status = valid) ของที่นั่ง ที่นั่งหนึ่งมีบัตร valid ได้ใบเดียว
     * param : -
     * return : HasOne Ticket
     */
    public function ticket(): HasOne
    {
        return $this->hasOne(Ticket::class, 't_seat_id', 's_id')->where('status', Ticket::STATUS_VALID);
    }

    /**
     * tickets()
     * ความสัมพันธ์กับบัตรทั้งหมดของที่นั่ง รวมบัตรที่ถูกยกเลิก/ออกใหม่แทน
     * param : -
     * return : HasMany Ticket
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 't_seat_id', 's_id');
    }

    /**
     * seatLockLogs()
     * ความสัมพันธ์กับประวัติการล็อกที่นั่ง
     * param : -
     * return : HasMany SeatLockLog
     */
    public function seatLockLogs(): HasMany
    {
        return $this->hasMany(SeatLockLog::class, 'sll_seat_id', 's_id');
    }
}
