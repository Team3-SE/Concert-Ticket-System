<?php

/**
 * Payment
 * การชำระเงิน (ตาราง payment)
 * Author :
 * Created :
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected $table = 'payment';
    protected $primaryKey = 'p_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    // Status Enumerations (payment.status)
    public const STATUS_PENDING   = 'PENDING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_FAILED    = 'FAILED';

    protected $fillable = [
        'p_booking_id',
        'amount',
        'method',
        'status',
        'provider_transaction_id',
        'payment_metadata',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'payment_metadata' => 'array',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    // ==========================================
    // Methods from Specification
    // ==========================================

    /**
     * processPayment()
     * เริ่มการชำระเงิน (PROMPTPAY หรือ CREDIT_CARD)
     *
     * @return bool true ถ้ารายการยังรอชำระ (PENDING)
     */
    public function processPayment(): bool
    {
        // ส่งข้อมูลไปยัง Payment Provider (stub)
        // TODO: เรียก Provider API จริง
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * verifyStatus()
     * ตรวจสอบสถานะธุรกรรมการชำระเงิน
     *
     * @return bool true ถ้าชำระเงินสำเร็จ (COMPLETED)
     */
    public function verifyStatus(): bool
    {
        // TODO: เรียก Provider API จริงเพื่อตรวจสอบสถานะ
        return $this->status === self::STATUS_COMPLETED;
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * booking()
     * ความสัมพันธ์กับการจอง
     *
     * @return BelongsTo Booking
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'p_booking_id', 'b_id');
    }

    /**
     * customerOrder()
     * ความสัมพันธ์กับคำสั่งซื้อ
     *
     * @return HasOne CustomerOrder
     */
    public function customerOrder(): HasOne
    {
        return $this->hasOne(CustomerOrder::class, 'uo_payment_id', 'p_id');
    }
}
