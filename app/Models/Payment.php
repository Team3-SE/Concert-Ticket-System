<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Payment
 * manage Payment entity and its relationships
 * Author : AI Agent
 * Updated : 27/09/2026 – removed deprecated columns (decline_code, risk_level, card_fingerprint),
 *                        those fields now live inside paymentMetadata JSON per spec v3.2.0
 */
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
     * processPayment(): Boolean
     * เริ่มการชำระเงินด้วย PROMPTPAY หรือ CREDIT_CARD
     * บันทึก ipAddress/deviceFingerprint ลงใน paymentMetadata
     */
    public function processPayment(): bool
    {
        // ส่งข้อมูลไปยัง Payment Provider (stub)
        // TODO: เรียก Provider API จริง
        return $this->status === 'PENDING';
    }

    /**
     * verifyStatus(): Boolean
     * ตรวจสอบสถานะธุรกรรมจาก Payment Provider
     * รวมถึง declineCode, cardFingerprint ใน paymentMetadata
     */
    public function verifyStatus(): bool
    {
        // TODO: เรียก Provider API จริงเพื่อตรวจสอบสถานะ
        return $this->status === 'COMPLETED';
    }

    // ==========================================
    // Relationships
    // ==========================================

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function customerOrder(): HasOne
    {
        return $this->hasOne(CustomerOrder::class, 'payment_id', 'payment_id');
    }
}
