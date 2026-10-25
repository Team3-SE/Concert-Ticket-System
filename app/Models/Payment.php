<?php

/**
 * Payment
 * การชำระเงิน (ตาราง payment)
 * Author :
 * Created :
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected $table = 'payment';
    protected $primaryKey = 'payment_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
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
     * param : -
     * return : bool – true ถ้ารายการยังรอชำระ (PENDING)
     */
    public function processPayment(): bool
    {
        // ส่งข้อมูลไปยัง Payment Provider (stub)
        // TODO: เรียก Provider API จริง
        return $this->status === 'PENDING';
    }

    /**
     * verifyStatus()
     * ตรวจสอบสถานะธุรกรรมการชำระเงิน
     * param : -
     * return : bool – true ถ้าชำระเงินสำเร็จ (COMPLETED)
     */
    public function verifyStatus(): bool
    {
        // TODO: เรียก Provider API จริงเพื่อตรวจสอบสถานะ
        return $this->status === 'COMPLETED';
    }

    // ==========================================
    // Relationships
    // ==========================================

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
     * customerOrder()
     * ความสัมพันธ์กับคำสั่งซื้อ
     * param : -
     * return : HasOne CustomerOrder
     */
    public function customerOrder(): HasOne
    {
        return $this->hasOne(CustomerOrder::class, 'payment_id', 'payment_id');
    }
}
