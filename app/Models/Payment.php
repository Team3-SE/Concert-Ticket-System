<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Payment
 * manage Payment entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Payment extends Model
{
    use HasUlids;

    protected $table = 'payment';
    protected $primaryKey = 'payment_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'payment_id', 'booking_id', 'amount', 'method', 'status',
        'payment_date', 'provider_transaction_id', 'decline_code',
        'risk_level', 'card_fingerprint',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function chargebacks(): HasMany
    {
        return $this->hasMany(Chargeback::class, 'payment_id', 'payment_id');
    }

    public function customerOrder(): HasOne
    {
        return $this->hasOne(CustomerOrder::class, 'payment_id', 'payment_id');
    }

    public function processPayment(): bool
    {
        $this->status = 'PENDING';
        return $this->save();
    }

    public function verifyStatus(): bool
    {
        return $this->status === 'COMPLETED';
    }

}
