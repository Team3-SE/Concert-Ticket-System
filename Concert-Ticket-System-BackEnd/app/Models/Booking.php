<?php

/**
 * Booking
 * การจองบัตรคอนเสิร์ต (ตาราง booking)
 * schema 3.4.2: 1 การจอง = 1 โซน (b_zone_id) เก็บ snapshot ชื่อโซน/ราคาไว้ที่ booking
 * โซนที่นั่งเก็บที่นั่งใน booking_seat, โซนยืนเก็บจำนวนใน standing_quantity และนับใน zone_inventory
 * Author :
 * Created :
 * Updated : 11/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\DB;

class Booking extends Model
{
    /* การจองที่มีรายการชำระเงินรอยืนยันอยู่ จะรอเพิ่มอีกกี่นาทีหลังหมดเวลา ก่อนปล่อยที่นั่ง (กันกรณีจ่ายเงินตอนใกล้หมดเวลา) */
    public const PAYMENT_GRACE_MINUTES = 2;

    // Status Enumerations (booking.status)
    public const STATUS_PENDING_PAYMENT = 'PENDING_PAYMENT';
    public const STATUS_CONFIRMED       = 'CONFIRMED';
    public const STATUS_CANCELLED       = 'CANCELLED';

    protected $table = 'booking';

    protected $primaryKey = 'b_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'booking_code',
        'b_user_id',
        'b_zone_id',
        'booking_date',
        'total_amount',
        'status',
        'expiry_time',
        'updated_at',
        'zone_price_snapshot',
        'zone_name_snapshot',
        'standing_quantity',
    ];

    protected $casts = [
        'booking_date'        => 'datetime',
        'expiry_time'         => 'datetime',
        'updated_at'          => 'datetime',
        'total_amount'        => 'decimal:2',
        'zone_price_snapshot' => 'decimal:2',
        'standing_quantity'   => 'integer',
    ];

    // ==========================================
    // Methods from Specification
    // ==========================================

    /**
     * isStanding()
     * ตรวจว่าเป็นการจองโซนยืน (มีจำนวนใน standing_quantity)
     * param : -
     * return : bool
     */
    public function isStanding(): bool
    {
        return (int) $this->standing_quantity > 0;
    }

    /**
     * ticketQuantity()
     * จำนวนบัตรในการจอง (โซนยืน = standing_quantity, โซนที่นั่ง = จำนวนที่นั่งใน booking_seat)
     * param : -
     * return : int
     */
    public function ticketQuantity(): int
    {
        return $this->isStanding() ? (int) $this->standing_quantity : $this->bookingSeats()->count();
    }

    /**
     * concertId()
     * รหัสคอนเสิร์ตของการจอง (schema 3.4.2 ไม่มี concert_id ในตาราง booking จึงดูจากโซนที่จอง)
     * param : -
     * return : int
     */
    public function concertId(): int
    {
        return (int) ($this->zone?->z_concert_id ?? 0);
    }

    /**
     * calculateTotal()
     * คำนวณยอดรวม = จำนวนบัตร x ราคาโซน ณ เวลาที่จอง (zone_price_snapshot)
     * param : -
     * return : float ยอดรวม
     */
    public function calculateTotal(): float
    {
        return $this->ticketQuantity() * (float) $this->zone_price_snapshot;
    }

    /**
     * confirmBooking()
     * ยืนยันการจองหลังชำระเงินสำเร็จ: สถานะ CONFIRMED, ที่นั่งเป็น sold, ปิดล็อกที่นั่ง (sold_at), โซนยืนย้ายจำนวนจาก held ไป sold
     * param : -
     * return : void
     */
    public function confirmBooking(): void
    {
        DB::transaction(function () {
            $this->update(['status' => self::STATUS_CONFIRMED]);

            foreach ($this->bookingSeats()->with('seat')->get() as $bookingSeat) {
                $bookingSeat->seat?->confirmSeat();
            }

            $this->seatLockLogs()->active()->update(['sold_at' => now()]);

            if ($this->isStanding()) {
                ZoneInventory::lockForZone((int) $this->b_zone_id)->sell((int) $this->standing_quantity);
            }
        });
    }

    /**
     * cancelBooking()
     * ยกเลิกการจอง (สถานะ CANCELLED)
     * param : -
     * return : void
     */
    public function cancelBooking(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * checkExpiry()
     * ตรวจว่าการจองที่รอชำระเงินหมดเวลาแล้วหรือยัง
     * param : -
     * return : bool – true ถ้าหมดเวลาแล้ว
     */
    public function checkExpiry(): bool
    {
        return $this->status === self::STATUS_PENDING_PAYMENT
            && $this->expiry_time !== null
            && $this->expiry_time->isPast();
    }

    /**
     * releaseSeats()
     * ยกเลิกการจองที่หมดเวลา/ถูกยกเลิก: ปลดล็อกที่นั่งกลับเป็น available, ปิดล็อกที่นั่ง (released_at),
     * คืนจำนวนบัตรยืนใน zone_inventory (held) และตั้งการจองเป็น CANCELLED
     * ทำเฉพาะการจองที่ยังรอชำระเงิน (ล็อกแถวก่อนตรวจ กันการปล่อยซ้ำตอน scheduler กับผู้ใช้กดพร้อมกัน)
     * param : -
     * return : void
     */
    public function releaseSeats(): void
    {
        DB::transaction(function () {
            $current = static::query()->whereKey($this->getKey())->lockForUpdate()->first();
            if ($current === null || $current->status !== self::STATUS_PENDING_PAYMENT) {
                return;
            }

            foreach ($this->bookingSeats()->with('seat')->get() as $bookingSeat) {
                if ($bookingSeat->seat?->seat_booking_status === Seat::STATUS_LOCKED) {
                    $bookingSeat->seat->unlockSeat();
                }
            }

            $this->seatLockLogs()->active()->update(['released_at' => now()]);

            if ($this->isStanding()) {
                ZoneInventory::lockForZone((int) $this->b_zone_id)->release((int) $this->standing_quantity);
            }

            // รายการชำระเงินที่ยังรอยืนยันของการจองนี้ ถือว่าไม่สำเร็จ
            $this->payments()->where('status', Payment::STATUS_PENDING)->update(['status' => Payment::STATUS_FAILED]);
            $this->cancelBooking();
        });
    }

    /**
     * releaseExpired(concertId)
     * ปล่อยที่นั่งของทุกการจองที่รอชำระเงินและเลยเวลา (10 นาที) แล้ว
     * การจองที่มีรายการชำระเงินรอยืนยันอยู่ จะรอเพิ่มอีก PAYMENT_GRACE_MINUTES นาที
     * param : concertId (ไม่ระบุ = ทุกคอนเสิร์ต)
     * return : int จำนวนการจองที่ถูกปล่อย
     */
    public static function releaseExpired(?int $concertId = null): int
    {
        $expired = static::query()
            ->where('status', self::STATUS_PENDING_PAYMENT)
            ->whereNotNull('expiry_time')
            ->where('expiry_time', '<=', now())
            ->where(function ($q) {
                $q->whereDoesntHave('payments', fn ($p) => $p->where('status', Payment::STATUS_PENDING))
                    ->orWhere('expiry_time', '<=', now()->subMinutes(self::PAYMENT_GRACE_MINUTES));
            })
            ->when($concertId, fn ($q) => $q->whereHas('zone', fn ($z) => $z->where('z_concert_id', $concertId)))
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
        return $this->belongsTo(AppUser::class, 'b_user_id', 'u_id');
    }

    /**
     * zone()
     * ความสัมพันธ์กับโซนที่จอง
     * param : -
     * return : BelongsTo Zone
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'b_zone_id', 'z_id');
    }

    /**
     * concert()
     * ความสัมพันธ์กับคอนเสิร์ตที่จอง (booking -> zone -> concert)
     * param : -
     * return : HasOneThrough Concert
     */
    public function concert(): HasOneThrough
    {
        return $this->hasOneThrough(Concert::class, Zone::class, 'z_id', 'c_id', 'b_zone_id', 'z_concert_id');
    }

    /**
     * bookingSeats()
     * ความสัมพันธ์กับที่นั่งในการจองนี้ (เฉพาะโซนที่นั่ง)
     * param : -
     * return : HasMany BookingSeat
     */
    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class, 'bs_booking_id', 'b_id');
    }

    /**
     * payments()
     * ความสัมพันธ์กับรายการชำระเงินของการจองนี้
     * param : -
     * return : HasMany Payment
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'p_booking_id', 'b_id');
    }

    /**
     * customerOrder()
     * ความสัมพันธ์กับคำสั่งซื้อของการจองนี้
     * param : -
     * return : HasOne CustomerOrder
     */
    public function customerOrder(): HasOne
    {
        return $this->hasOne(CustomerOrder::class, 'uo_booking_id', 'b_id');
    }

    /**
     * seatLockLogs()
     * ความสัมพันธ์กับประวัติการล็อกที่นั่งของการจองนี้
     * param : -
     * return : HasMany SeatLockLog
     */
    public function seatLockLogs(): HasMany
    {
        return $this->hasMany(SeatLockLog::class, 'sll_booking_id', 'b_id');
    }

    /**
     * systemAnomalies()
     * ความสัมพันธ์กับความผิดปกติที่เกี่ยวกับการจองนี้
     * param : -
     * return : HasMany SystemAnomaly
     */
    public function systemAnomalies(): HasMany
    {
        return $this->hasMany(SystemAnomaly::class, 'sa_booking_id', 'b_id');
    }
}
