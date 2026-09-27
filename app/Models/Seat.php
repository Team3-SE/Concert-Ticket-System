<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Seat
 * manage Seat entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 * Updated : 27/09/2026 – fixed seat_booking_status column, confirmSeat uses SOLD
 */
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
     * lockSeat(): void
     * เปลี่ยน seat_booking_status ของ Seat เป็น LOCKED ชั่วคราวระหว่างขั้นตอนการจอง
     */
    public function lockSeat(): void
    {
        $this->update(['seat_booking_status' => 'LOCKED']);
    }

    /**
     * unlockSeat(): void
     * ปลด Seat ที่ถูกล็อกให้กลับมาเป็น AVAILABLE เมื่อยกเลิกหรือ Lock หมดอายุ
     */
    public function unlockSeat(): void
    {
        $this->update(['seat_booking_status' => 'AVAILABLE']);
    }

    /**
     * confirmSeat(): void
     * ยืนยัน Seat หลังการจองสำเร็จและเปลี่ยน seat_booking_status เป็น SOLD
     */
    public function confirmSeat(): void
    {
        $this->update(['seat_booking_status' => 'SOLD']);
    }

    // ==========================================
    // Relationships
    // ==========================================

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_id', 'zone_id');
    }

    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class, 'seat_id', 'seat_id');
    }

    public function ticket(): HasOne
    {
        return $this->hasOne(Ticket::class, 'seat_id', 'seat_id');
    }

    public function seatLockLogs(): HasMany
    {
        return $this->hasMany(SeatLockLog::class, 'seat_id', 'seat_id');
    }

    public function zoneSeatPattern(): BelongsTo
    {
        return $this->belongsTo(ZoneSeatPattern::class, 'zone_seat_pattern_pattern_id', 'pattern_id');
    }
}
