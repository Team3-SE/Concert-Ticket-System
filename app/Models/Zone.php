<?php

/**
 * Zone
 * โซนที่นั่งของคอนเสิร์ต (ตาราง zone)
 * Author :
 * Created :
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Zone extends Model
{
    protected $table = 'zone';
    protected $primaryKey = 'zone_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'concert_id',
        'name',
        'zone_type',
        'price',
        'capacity',
        'total_rows',
        'seats_per_row',
        'seat_start_number',
        'color',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'price'            => 'decimal:2',
        'capacity'         => 'integer',
        'total_rows'       => 'integer',
        'seats_per_row'    => 'integer',
        'seat_start_number'=> 'integer',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    // ==========================================
    // Methods from Specification
    // ==========================================

    /**
     * getAvailableSeats()
     * ดึงที่นั่งที่ยังว่างในโซน (สำหรับโซน SEATED)
     * param : -
     * return : HasMany Seat
     */
    public function getAvailableSeats(): HasMany
    {
        return $this->seats()->where('seat_booking_status', 'AVAILABLE');
    }

    /**
     * getAvailableCapacity()
     * คำนวณจำนวนบัตรที่ยังจองได้ในโซน (STANDING ใช้ capacity ลบบัตรที่ขายแล้ว, SEATED นับที่นั่งว่าง)
     * param : -
     * return : int
     */
    public function getAvailableCapacity(): int
    {
        if ($this->zone_type === 'STANDING') {
            $sold = $this->tickets()
                ->whereHas('order', function ($q) {
                    $q->whereNotNull('order_id');
                })
                ->sum('quantity');

            return max(0, (int) $this->capacity - (int) $sold);
        }

        return $this->getAvailableSeats()->count();
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * concert()
     * ความสัมพันธ์กับคอนเสิร์ต
     * param : -
     * return : BelongsTo Concert
     */
    public function concert(): BelongsTo
    {
        return $this->belongsTo(Concert::class, 'concert_id', 'concert_id');
    }

    /**
     * seatPatterns()
     * ความสัมพันธ์กับรูปแบบการจัดที่นั่ง
     * param : -
     * return : HasMany ZoneSeatPattern
     */
    public function seatPatterns(): HasMany
    {
        return $this->hasMany(ZoneSeatPattern::class, 'zone_id', 'zone_id');
    }

    /**
     * seats()
     * ความสัมพันธ์กับที่นั่งในโซน
     * param : -
     * return : HasMany Seat
     */
    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class, 'zone_id', 'zone_id');
    }

    /**
     * tickets()
     * ความสัมพันธ์กับบัตรในโซน
     * param : -
     * return : HasMany Ticket
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'zone_id', 'zone_id');
    }
}
