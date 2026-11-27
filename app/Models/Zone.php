<?php

/**
 * Zone
 * โซนที่นั่งของคอนเสิร์ต (ตาราง zone)
 * Author :
 * Created :
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Zone extends Model
{
    protected $table = 'zone';
    protected $primaryKey = 'z_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    // Zone Type Enumerations (zone.zone_type)
    public const TYPE_SEATED   = 'SEATED';
    public const TYPE_STANDING = 'STANDING';

    // schema 3.4.2: รูปแบบการตั้งชื่อที่นั่ง (pattern_type, prefix, row_alphabet_start, custom_items) ย้ายมาอยู่ในตาราง zone แทน zone_seat_pattern
    protected $fillable = [
        'z_concert_id',
        'name',
        'zone_type',
        'pattern_type',
        'prefix',
        'price',
        'capacity',
        'total_rows',
        'seats_per_row',
        'seat_number_start',
        'row_alphabet_start',
        'color',
        'custom_items',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'price'             => 'decimal:2',
        'capacity'          => 'integer',
        'total_rows'        => 'integer',
        'seats_per_row'     => 'integer',
        'seat_number_start' => 'integer',
        'custom_items'      => 'array',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    // ==========================================
    // Methods from Specification
    // ==========================================

    /**
     * isStanding()
     * ตรวจว่าเป็นโซนยืน (ไม่มีที่นั่ง นับจำนวนผ่าน zone_inventory)
     *
     * @return bool true หรือ false ตามที่อธิบายข้างต้น
     */
    public function isStanding(): bool
    {
        return $this->zone_type === self::TYPE_STANDING;
    }

    /**
     * getAvailableSeats()
     * ดึงที่นั่งที่ยังว่างและเปิดใช้งานอยู่ในโซน (สำหรับโซน SEATED) ที่นั่งที่ปิดใช้งาน (disabled / blocked) ไม่นับ
     *
     * @return HasMany Seat
     */
    public function getAvailableSeats(): HasMany
    {
        return $this->seats()
            ->where('seat_booking_status', Seat::STATUS_AVAILABLE)
            ->where('seat_status', Seat::SEAT_ACTIVE);
    }

    /**
     * getAvailableCapacity()
     * คำนวณจำนวนบัตรที่ยังจองได้ในโซน
     * STANDING ใช้ capacity ลบจำนวนที่จองรอชำระ (held) และที่ขายแล้ว (sold) ใน zone_inventory, SEATED นับที่นั่งว่าง
     *
     * @return int ค่าจำนวนตามที่อธิบายข้างต้น
     */
    public function getAvailableCapacity(): int
    {
        if ($this->isStanding()) {
            $inventory = $this->inventory;

            return $inventory ? $inventory->available((int) $this->capacity) : (int) $this->capacity;
        }

        return $this->getAvailableSeats()->count();
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * concert()
     * ความสัมพันธ์กับคอนเสิร์ต
     *
     * @return BelongsTo Concert
     */
    public function concert(): BelongsTo
    {
        return $this->belongsTo(Concert::class, 'z_concert_id', 'c_id');
    }

    /**
     * inventory()
     * ความสัมพันธ์กับตัวนับบัตรของโซนยืน (held / sold)
     *
     * @return HasOne ZoneInventory
     */
    public function inventory(): HasOne
    {
        return $this->hasOne(ZoneInventory::class, 'zi_zone_id', 'z_id');
    }

    /**
     * seats()
     * ความสัมพันธ์กับที่นั่งในโซน
     *
     * @return HasMany Seat
     */
    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class, 's_zone_id', 'z_id');
    }

    /**
     * bookings()
     * ความสัมพันธ์กับการจองของโซน (schema 3.4.2: 1 การจอง = 1 โซน)
     *
     * @return HasMany Booking
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'b_zone_id', 'z_id');
    }

    /**
     * tickets()
     * ความสัมพันธ์กับบัตรในโซน
     *
     * @return HasMany Ticket
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 't_zone_id', 'z_id');
    }
}
