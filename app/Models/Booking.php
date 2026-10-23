<?php

/**
 * Booking
 * การจองบัตรคอนเสิร์ต (ตาราง booking)
 * Author :
 * Created :
 * Updated : 23/10/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    protected $table = 'booking';

    protected $primaryKey = 'booking_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'booking_code',
        'user_id',          // schema v3.1.0: booking.user_id (เดิม customer_id)
        'concert_id',
        'booking_date',
        'total_amount',
        'status',
        'expiry_time',
        'updated_at',
    ];

    protected $casts = [
        'booking_date' => 'datetime',
        'expiry_time' => 'datetime',
        'updated_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    // ==========================================
    // Methods from Specification
    // ==========================================

    /**
     * calculateTotal()
     * คำนวณยอดรวมจากราคา snapshot ของที่นั่งที่จอง
     * param : -
     * return : float ยอดรวม
     */
    public function calculateTotal(): float
    {
        return (float) $this->bookingSeats()->sum('unit_price_snapshot');
    }

    /**
     * confirmBooking()
     * ยืนยันการจองหลังชำระเงินสำเร็จ (สถานะ CONFIRMED)
     * param : -
     * return : void
     */
    public function confirmBooking(): void
    {
        $this->update(['status' => 'CONFIRMED']);
    }

    /**
     * cancelBooking()
     * ยกเลิกการจอง (สถานะ CANCELLED)
     * param : -
     * return : void
     */
    public function cancelBooking(): void
    {
        $this->update(['status' => 'CANCELLED']);
    }

    /**
     * checkExpiry()
     * ตรวจว่าการจองที่รอชำระเงินหมดเวลาแล้วหรือยัง
     * param : -
     * return : bool – true ถ้าหมดเวลาแล้ว
     */
    public function checkExpiry(): bool
    {
        return $this->status === 'PENDING_PAYMENT'
            && $this->expiry_time !== null
            && $this->expiry_time->isPast();
    }

    /**
     * releaseSeats()
     * ยกเลิกการจองที่หมดเวลา/ถูกยกเลิก: ปลดล็อกที่นั่งกลับเป็น AVAILABLE, ปิด SeatLockLog เป็น EXPIRED, ตั้งการจองเป็น CANCELLED
     * param : -
     * return : void
     */
    public function releaseSeats(): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () {
            foreach ($this->bookingSeats()->with('seat')->get() as $bookingSeat) {
                if ($bookingSeat->seat?->seat_booking_status === 'LOCKED') {
                    $bookingSeat->seat->unlockSeat();
                }
            }
            $this->seatLockLogs()->where('status', 'LOCKED')->update([
                'status'      => 'EXPIRED',
                'released_at' => now(),
            ]);
            $this->cancelBooking();
        });
    }

    /**
     * releaseExpired(concertId)
     * ปล่อยที่นั่งของทุกการจองที่รอชำระเงินและเลยเวลา (10 นาที) แล้ว
     * param : concertId (ไม่ระบุ = ทุกคอนเสิร์ต)
     * return : int จำนวนการจองที่ถูกปล่อย
     */
    public static function releaseExpired(?int $concertId = null): int
    {
        $expired = static::query()
            ->where('status', 'PENDING_PAYMENT')
            ->whereNotNull('expiry_time')
            ->where('expiry_time', '<=', now())
            ->when($concertId, fn ($q) => $q->where('concert_id', $concertId))
            ->get();

        foreach ($expired as $booking) {
            $booking->releaseSeats();
        }

        return $expired->count();
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * customer()
     * ความสัมพันธ์กับผู้ใช้ที่จอง
     * param : -
     * return : BelongsTo AppUser
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id', 'user_id');
    }

    /**
     * concert()
     * ความสัมพันธ์กับคอนเสิร์ตที่จอง
     * param : -
     * return : BelongsTo Concert
     */
    public function concert(): BelongsTo
    {
        return $this->belongsTo(Concert::class, 'concert_id', 'concert_id');
    }

    /**
     * bookingSeats()
     * ความสัมพันธ์กับที่นั่งในการจองนี้
     * param : -
     * return : HasMany BookingSeat
     */
    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class, 'booking_id', 'booking_id');
    }

    /**
     * bookingStandingItems()
     * ความสัมพันธ์กับจำนวนบัตรยืนที่จองไว้
     * param : -
     * return : HasMany BookingStandingItem
     */
    public function bookingStandingItems(): HasMany
    {
        return $this->hasMany(BookingStandingItem::class, 'booking_id', 'booking_id');
    }

    /**
     * payments()
     * ความสัมพันธ์กับรายการชำระเงินของการจองนี้
     * param : -
     * return : HasMany Payment
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'booking_id', 'booking_id');
    }

    /**
     * customerOrder()
     * ความสัมพันธ์กับคำสั่งซื้อของการจองนี้
     * param : -
     * return : HasOne CustomerOrder
     */
    public function customerOrder(): HasOne
    {
        return $this->hasOne(CustomerOrder::class, 'booking_id', 'booking_id');
    }

    /**
     * seatLockLogs()
     * ความสัมพันธ์กับประวัติการล็อกที่นั่งของการจองนี้
     * param : -
     * return : HasMany SeatLockLog
     */
    public function seatLockLogs(): HasMany
    {
        return $this->hasMany(SeatLockLog::class, 'booking_id', 'booking_id');
    }

    /**
     * systemAnomalies()
     * ความสัมพันธ์กับความผิดปกติที่เกี่ยวกับการจองนี้
     * param : -
     * return : HasMany SystemAnomaly
     */
    public function systemAnomalies(): HasMany
    {
        return $this->hasMany(SystemAnomaly::class, 'booking_id', 'booking_id');
    }
}
