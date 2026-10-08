<?php

/**
 * Seat
 * ที่นั่ง (ตาราง seat)
 * Author :
 * Created :
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Seat extends Model
{
    protected $table = 'seat';
    protected $primaryKey = 'seat_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'zone_id',
        'row',
        'number',
        'seat_booking_status',
        'seat_status',
        'zone_seat_pattern_pattern_id',
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
     * lockSeat()
     * ล็อกที่นั่งชั่วคราวระหว่างการจอง (LOCKED)
     * param : -
     * return : void
     */
    public function lockSeat(): void
    {
        $this->update(['seat_booking_status' => 'LOCKED']);
    }

    /**
     * unlockSeat()
     * ปลดล็อกที่นั่งกลับเป็น AVAILABLE เมื่อยกเลิกหรือหมดเวลา
     * param : -
     * return : void
     */
    public function unlockSeat(): void
    {
        $this->update(['seat_booking_status' => 'AVAILABLE']);
    }

    /**
     * confirmSeat()
     * ยืนยันที่นั่งหลังชำระเงินสำเร็จ (SOLD)
     * param : -
     * return : void
     */
    public function confirmSeat(): void
    {
        $this->update(['seat_booking_status' => 'SOLD']);
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
        return $this->belongsTo(Zone::class, 'zone_id', 'zone_id');
    }

    /**
     * bookingSeats()
     * ความสัมพันธ์กับรายการจองของที่นั่ง
     * param : -
     * return : HasMany BookingSeat
     */
    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class, 'seat_id', 'seat_id');
    }

    /**
     * ticket()
     * ความสัมพันธ์กับบัตรของที่นั่ง
     * param : -
     * return : HasOne Ticket
     */
    public function ticket(): HasOne
    {
        return $this->hasOne(Ticket::class, 'seat_id', 'seat_id');
    }

    /**
     * seatLockLogs()
     * ความสัมพันธ์กับประวัติการล็อกที่นั่ง
     * param : -
     * return : HasMany SeatLockLog
     */
    public function seatLockLogs(): HasMany
    {
        return $this->hasMany(SeatLockLog::class, 'seat_id', 'seat_id');
    }

    /**
     * zoneSeatPattern()
     * ความสัมพันธ์กับรูปแบบการจัดที่นั่ง
     * param : -
     * return : BelongsTo ZoneSeatPattern
     */
    public function zoneSeatPattern(): BelongsTo
    {
        return $this->belongsTo(ZoneSeatPattern::class, 'zone_seat_pattern_pattern_id', 'pattern_id');
    }
}
