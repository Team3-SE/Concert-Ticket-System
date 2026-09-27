<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Booking
 * manage Booking entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 * Updated : 27/09/2026 – fixed customer FK, removed deprecated BookingStandingItem
 */
class Booking extends Model
{
    protected $table = 'booking';
    protected $primaryKey = 'booking_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'booking_code',
        'customer_id',
        'concert_id',
        'booking_date',
        'total_amount',
        'status',
        'expiry_time',
        'updated_at',
    ];

    protected $casts = [
        'booking_date' => 'datetime',
        'expiry_time'  => 'datetime',
        'updated_at'   => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    // ==========================================
    // Methods from Specification
    // ==========================================

    /**
     * calculateTotal(): Double
     * คำนวณยอดรวมของ Booking จาก unit_price_snapshot ของ BookingSeat
     */
    public function calculateTotal(): float
    {
        return (float) $this->bookingSeats()->sum('unit_price_snapshot');
    }

    /**
     * confirmBooking(): void
     * ยืนยัน Booking หลังการชำระเงินสำเร็จและเปลี่ยนสถานะเป็น CONFIRMED
     */
    public function confirmBooking(): void
    {
        $this->update(['status' => 'CONFIRMED']);
    }

    /**
     * cancelBooking(): void
     * ยกเลิก Booking และเปลี่ยนสถานะเป็น CANCELLED
     */
    public function cancelBooking(): void
    {
        $this->update(['status' => 'CANCELLED']);
    }

    /**
     * checkExpiry(): Boolean
     * ตรวจสอบว่า Booking ที่รอชำระเงินเกิน expiryTime และหมดอายุแล้วหรือไม่
     */
    public function checkExpiry(): bool
    {
        return $this->status === 'PENDING_PAYMENT'
            && $this->expiry_time !== null
            && $this->expiry_time->isPast();
    }

    // ==========================================
    // Relationships
    // ==========================================

    public function customer(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'customer_id', 'user_id');
    }

    public function concert(): BelongsTo
    {
        return $this->belongsTo(Concert::class, 'concert_id', 'concert_id');
    }

    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class, 'booking_id', 'booking_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'booking_id', 'booking_id');
    }

    public function customerOrder(): HasOne
    {
        return $this->hasOne(CustomerOrder::class, 'booking_id', 'booking_id');
    }

    public function seatLockLogs(): HasMany
    {
        return $this->hasMany(SeatLockLog::class, 'booking_id', 'booking_id');
    }

    public function systemAnomalies(): HasMany
    {
        return $this->hasMany(SystemAnomaly::class, 'booking_id', 'booking_id');
    }
}
