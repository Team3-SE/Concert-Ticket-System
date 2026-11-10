<?php

/**
 * Concert
 * คอนเสิร์ต (ตาราง concert)
 * Author :
 * Created :
 * Updated : 10/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Concert extends Model
{
    // ลบคอนเสิร์ตแบบ soft delete ด้วยคอลัมน์ deleted_at (แทน is_deleted เดิม) query ปกติจะไม่ดึงคอนเสิร์ตที่ถูกลบมาเอง
    use SoftDeletes;

    protected $table = 'concert';
    protected $primaryKey = 'c_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    // Status Enumerations (concert.status)
    public const STATUS_DRAFT     = 'DRAFT';
    public const STATUS_SCHEDULED = 'SCHEDULED';
    public const STATUS_ON_SALE   = 'ON_SALE';
    public const STATUS_SOLD_OUT  = 'SOLD_OUT';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'c_organization_id',
        'concert_code',
        'name',
        'artist',
        'venue',
        'event_datetime',
        'description',
        'phone',
        'email',
        'facebook',
        'line',
        'poster_path',
        'banner_path',
        'seat_map_image_path',
        'sale_start_datetime',
        'sale_end_datetime',
        'max_ticket_per_order',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'event_datetime'       => 'datetime',
        'sale_start_datetime'  => 'datetime',
        'sale_end_datetime'    => 'datetime',
        'max_ticket_per_order' => 'integer',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
        'deleted_at'           => 'datetime',
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
     * สรุปรายละเอียดคอนเสิร์ต (รหัส ศิลปิน สถานที่ วันเวลา สถานะ)
     * param : -
     * return : string
     */
    public function getDetails(): string
    {
        return "{$this->name} (code: {$this->concert_code}) by {$this->artist} at {$this->venue} on {$this->event_datetime} | status: {$this->status}";
    }

    /**
     * contacts()
     * ช่องทางติดต่อของคอนเสิร์ตที่กรอกไว้ (schema 3.4.2 ย้ายมาจากตาราง organization)
     * param : -
     * return : array ['phone' => ..., 'email' => ..., 'facebook' => ..., 'line' => ...] เฉพาะช่องที่มีค่า
     */
    public function contacts(): array
    {
        return array_filter([
            'phone'    => $this->phone,
            'email'    => $this->email,
            'facebook' => $this->facebook,
            'line'     => $this->line,
        ], fn ($value) => filled($value));
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
        return $this->belongsTo(Organization::class, 'c_organization_id', 'org_id');
    }

    /**
     * zones()
     * ความสัมพันธ์กับโซนของคอนเสิร์ต
     * param : -
     * return : HasMany Zone
     */
    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class, 'z_concert_id', 'c_id');
    }

    /**
     * bookings()
     * ความสัมพันธ์กับการจองของคอนเสิร์ต (schema 3.4.2: booking ผูกกับโซน จึงดึงผ่านตาราง zone)
     * param : -
     * return : HasManyThrough Booking
     */
    public function bookings(): HasManyThrough
    {
        return $this->hasManyThrough(Booking::class, Zone::class, 'z_concert_id', 'b_zone_id', 'c_id', 'z_id');
    }

    /**
     * tickets()
     * ความสัมพันธ์กับบัตรของคอนเสิร์ต (ดึงผ่านตาราง zone)
     * param : -
     * return : HasManyThrough Ticket
     */
    public function tickets(): HasManyThrough
    {
        return $this->hasManyThrough(Ticket::class, Zone::class, 'z_concert_id', 't_zone_id', 'c_id', 'z_id');
    }

    /**
     * staffConcerts()
     * ความสัมพันธ์กับพนักงานที่ได้รับมอบหมาย
     * param : -
     * return : HasMany StaffConcert
     */
    public function staffConcerts(): HasMany
    {
        return $this->hasMany(StaffConcert::class, 'sc_concert_id', 'c_id');
    }
}
