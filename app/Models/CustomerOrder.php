<?php

/**
 * CustomerOrder
 * คำสั่งซื้อหลังชำระเงินสำเร็จ (ตาราง user_order )
 * Author :
 * Created :
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CustomerOrder extends Model
{
    protected $table = 'user_order';
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
        'organization_name_snapshot',
        'user_fiestname_snapshot',   // สะกดตาม schema v3.1.0 (fiestname)
        'user_lastname_snapshot',
        'user_phone_snapshot',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'event_datetime_snapshot' => 'datetime',
    ];

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
     * payment()
     * ความสัมพันธ์กับการชำระเงิน
     * param : -
     * return : BelongsTo Payment
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }

    /**
     * tickets()
     * ความสัมพันธ์กับบัตรของคำสั่งซื้อ
     * param : -
     * return : HasMany Ticket
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'order_id', 'order_id');
    }

    // TODO: generateTickets should be handled in TicketController
    /*อันนี้ไม่มีในคลาส Ai เพิ่มมา */
    /**
     * booted()
     * ตั้งค่าเริ่มต้นตอนสร้างคำสั่งซื้อ (order_code และ order_date)
     * param : -
     * return : void
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
