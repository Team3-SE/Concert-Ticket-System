<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Zone
 * manage Zone entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 * Updated : 27/09/2026 – fixed seat_booking_status column, removed deprecated BookingStandingItem
 */
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
     * getAvailableSeats(): List<Seat>
     * ดึงรายการ Seat ที่มี seat_booking_status เป็น AVAILABLE สำหรับ Zone แบบ SEATED
     */
    public function getAvailableSeats(): HasMany
    {
        return $this->seats()->where('seat_booking_status', 'AVAILABLE');
    }

    /**
     * getAvailableCapacity(): Integer
     * คำนวณหรือดึงจำนวนบัตรที่ยังสามารถจองได้ใน Zone
     * สำหรับ STANDING: ใช้ capacity ลบด้วยจำนวน Ticket ที่ขายไปแล้วใน Zone นี้
     * สำหรับ SEATED: นับจำนวน Seat ที่ยัง AVAILABLE
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

    public function concert(): BelongsTo
    {
        return $this->belongsTo(Concert::class, 'concert_id', 'concert_id');
    }

    public function seatPatterns(): HasMany
    {
        return $this->hasMany(ZoneSeatPattern::class, 'zone_id', 'zone_id');
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class, 'zone_id', 'zone_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'zone_id', 'zone_id');
    }
}
