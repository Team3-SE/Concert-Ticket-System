<?php

/**
 * CustomerOrder
 * คำสั่งซื้อหลังชำระเงินสำเร็จ (ตาราง user_order)
 * schema 3.4.2: เก็บ snapshot ชื่อโซน ราคาต่อใบ ยอดรวม จำนวนบัตร และอีเมลผู้ซื้อไว้ที่คำสั่งซื้อ
 * Author :
 * Created :
 * Updated : 10/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CustomerOrder extends Model
{
    protected $table = 'user_order';
    protected $primaryKey = 'uo_id';
    public $timestamps = false;

    protected $fillable = [
        'uo_booking_id',
        'uo_payment_id',
        'order_date',
        'order_code',
        'concert_name_snapshot',
        'artist_snapshot',
        'venue_snapshot',
        'event_datetime_snapshot',
        'organization_name_snapshot',
        'user_firstname_snapshot',
        'user_lastname_snapshot',
        'user_phone_snapshot',
        'user_email_snapshot',
        'zone_name_snapshot',
        'unit_price_snapshot',
        'total_amount_snapshot',
        'quantity',
    ];

    protected $casts = [
        'order_date'              => 'datetime',
        'event_datetime_snapshot' => 'datetime',
        'unit_price_snapshot'     => 'decimal:2',
        'total_amount_snapshot'   => 'decimal:2',
        'quantity'                => 'integer',
    ];

    /**
     * booking()
     * ความสัมพันธ์กับการจอง
     * param : -
     * return : BelongsTo Booking
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'uo_booking_id', 'b_id');
    }

    /**
     * payment()
     * ความสัมพันธ์กับการชำระเงิน
     * param : -
     * return : BelongsTo Payment
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'uo_payment_id', 'p_id');
    }

    /**
     * tickets()
     * ความสัมพันธ์กับบัตรของคำสั่งซื้อ (รวมบัตรที่ถูกยกเลิก/ออกใหม่แทน)
     * param : -
     * return : HasMany Ticket
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 't_order_id', 'uo_id');
    }

    /**
     * validTickets()
     * ความสัมพันธ์กับบัตรที่ยังใช้ได้ (status = valid)
     * param : -
     * return : HasMany Ticket
     */
    public function validTickets(): HasMany
    {
        return $this->tickets()->where('status', Ticket::STATUS_VALID);
    }

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
