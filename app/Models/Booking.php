<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Booking
 * manage Booking entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Booking extends Model
{
    use HasUlids;

    protected $table = 'booking';
    protected $primaryKey = 'booking_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'booking_id', 'booking_code', 'customer_id', 'concert_id',
        'booking_date', 'total_amount', 'status', 'expiry_time',
    ];

    protected $casts = [
        'booking_date' => 'datetime',
        'expiry_time' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function concert(): BelongsTo
    {
        return $this->belongsTo(Concert::class, 'concert_id', 'concert_id');
    }

    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class, 'booking_id', 'booking_id');
    }

    public function standingItems(): HasMany
    {
        return $this->hasMany(BookingStandingItem::class, 'booking_id', 'booking_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'booking_id', 'booking_id');
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

    public function calculateTotal(): float
    {
        return (float) $this->bookingSeats->sum('unit_price_snapshot')
            + (float) $this->standingItems->sum(function (BookingStandingItem $item) {
                return $item->quantity * $item->unit_price_snapshot;
            });
    }

    public function confirmBooking(): void
    {
        $this->update(['status' => 'CONFIRMED']);
    }

    public function cancelBooking(): void
    {
        $this->update(['status' => 'CANCELLED']);
    }

    public function checkExpiry(): bool
    {
        return $this->status === 'PENDING_PAYMENT' && $this->expiry_time !== null
            && $this->expiry_time->isPast();
    }

}
