<?php

/**
 * Concert
 * คอนเสิร์ต (ตาราง concert)
 * Author :
 * Created :
 * Updated : 21/10/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Concert extends Model
{
    protected $table = 'concert';
    protected $primaryKey = 'concert_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'concert_code',
        'name',
        'artist',
        'venue',
        'address',
        'event_datetime',
        'description',
        'poster_path',
        'banner_path',
        'seat_map_image_path',
        'sale_start_datetime',
        'sale_end_datetime',
        'max_ticket_per_order',
        'status',
        'is_deleted',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'event_datetime'      => 'datetime',
        'sale_start_datetime' => 'datetime',
        'sale_end_datetime'   => 'datetime',
        'max_ticket_per_order'=> 'integer',
        'is_deleted'          => 'boolean',
    ];

    // ==========================================
    // Methods from Specification
    // ==========================================


    /* รูปแบบ concert_code ที่แปลงเป็น Public Code ได้: ตัวอักษร 1 ตัว + ตัวเลข 1-6 หลัก (เช่น C0024, C000024) */
    public const CODE_INPUT_PATTERN = '/^[A-Za-z](?!0+$)[0-9]{1,6}$/';

    /**
     * publicCode()
     * รหัสคอนเสิร์ตแบบ Public Code (ตัวอักษร 1 ตัว + ตัวเลข 6 หลัก เช่น C0024 -> C000024)
     * ใช้เป็นส่วน CONCERT ของรหัส ORD / TKT / ERR ตามเอกสาร Public Code Format Specification V2.0.0
     * param : -
     * return : string
     */
    public function publicCode(): string
    {
        return self::normalizePublicCode((string) $this->concert_code);
    }

    /**
     * normalizePublicCode(concertCode)
     * แปลง concert_code เป็นรูปแบบ [A-Z][0-9]{6} (เลข 000000 ใช้ไม่ได้ตามเอกสาร)
     * param : concertCode
     * return : string
     */
    public static function normalizePublicCode(string $concertCode): string
    {
        if (!preg_match('/^([A-Z])([0-9]{1,6})$/i', trim($concertCode), $matches) || (int) $matches[2] === 0) {
            throw new \RuntimeException("Concert code [{$concertCode}] cannot be used in a public code.");
        }

        return strtoupper($matches[1]) . str_pad($matches[2], 6, '0', STR_PAD_LEFT);
    }
    /**
     * getDetails()
     * สรุปรายละเอียดคอนเสิร์ต (รหัส ศิลปิน สถานที่ วันเวลา ช่วงขายบัตร สถานะ)
     * param : -
     * return : string
     */
    public function getDetails(): string
    {
        return "{$this->name} (code: {$this->concert_code}) by {$this->artist} at {$this->venue}, {$this->address} on {$this->event_datetime} | status: {$this->status}";
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * organization()
     * ความสัมพันธ์กับองค์กรผู้จัด
     * param : -
     * return : BelongsTo Organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id', 'organization_id');
    }

    /**
     * zones()
     * ความสัมพันธ์กับโซนของคอนเสิร์ต
     * param : -
     * return : HasMany Zone
     */
    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class, 'concert_id', 'concert_id');
    }

    /**
     * bookings()
     * ความสัมพันธ์กับการจองของคอนเสิร์ต
     * param : -
     * return : HasMany Booking
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'concert_id', 'concert_id');
    }

    /**
     * tickets()
     * ความสัมพันธ์กับบัตรของคอนเสิร์ต
     * param : -
     * return : HasMany Ticket
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'zone_id', 'concert_id');
    }

    /**
     * staffConcerts()
     * ความสัมพันธ์กับพนักงานที่ได้รับมอบหมาย
     * param : -
     * return : HasMany StaffConcert
     */
    public function staffConcerts(): HasMany
    {
        return $this->hasMany(StaffConcert::class, 'concert_id', 'concert_id');
    }
}
