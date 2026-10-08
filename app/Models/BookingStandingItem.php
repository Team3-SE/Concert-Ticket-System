<?php

/**
 * BookingStandingItem
 * รายการบัตรยืนในการจอง (ตาราง booking_standing_item )
 * Author :
 * Created :
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingStandingItem extends Model
{
    protected $table = 'booking_standing_item';
    protected $primaryKey = null;
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'booking_id', 'zone_id', 'quantity',
        'zone_name_snapshot', 'unit_price_snapshot',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_snapshot' => 'decimal:2',
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
     * zone()
     * ความสัมพันธ์กับโซนยืน
     * param : -
     * return : BelongsTo Zone
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_id', 'zone_id');
    }
}
