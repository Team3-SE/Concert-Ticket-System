<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CustomerOrder extends Model
{
    protected $table = 'customer_order';
    protected $primaryKey = 'order_id';
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'payment_id',
        'order_date',
        'order_code',
        'concert_name_snapshot',
        'artist_snapshot',
        'venue_snapshot',
        'event_datetime_snapshot',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'event_datetime_snapshot' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'order_id', 'order_id');
    }

    // TODO: generateTickets should be handled in TicketController
    /*อันนี้ไม่มีในคลาส Ai เพิ่มมา */
    /**
     * สร้าง orderCode อัตโนมัติตอนสร้าง Order (เช่น ORD-XXXXXXXX)
     */
    protected static function booted(): void
    {
        static::creating(function (CustomerOrder $order) {
            if (empty($order->order_code)) {
                $order->order_code = 'ORD-' . Str::upper(Str::random(8));
            }
            if (empty($order->order_date)) {
                $order->order_date = now();
            }
        });
    }
}
