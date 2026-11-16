<?php

/**
 * Concert
 * คอนเสิร์ต (ตาราง concert)
 * Author :
 * Created :
 * Updated : 11/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

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

    // สถานะสำหรับแสดงผลที่ระบบคำนวณเอง (ไม่ได้เก็บใน concert.status) ดู displayStatus()
    public const DISPLAY_COMPLETED   = 'COMPLETED';     // เลยเวลาแสดงแล้ว
    public const DISPLAY_SALE_CLOSED = 'SALE_CLOSED';   // เลยวันปิดขายแล้ว แต่ยังไม่ถึงวันแสดง

    /* จำนวนบัตรที่ยังจองได้ที่คำนวณไว้แล้ว (กัน query ซ้ำในหน้าเดียวกัน) */
    private ?int $availableTicketsCache = null;

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
    // Sale state (สถานะการขายที่ระบบคำนวณ)
    // ==========================================

    /**
     * availableTicketsSql()
     * SQL นับบัตรที่ยังจองได้ของคอนเสิร์ต (อ้างคอลัมน์ concert.c_id ของ query หลัก)
     * = ที่นั่งว่างที่เปิดใช้งานในโซนที่นั่ง + (ความจุ - held - sold) ของโซนยืน
     * ที่นั่งที่ล็อกรอชำระเงินนับว่าไม่ว่าง เมื่อหมดเวลาแล้วถูกปล่อย (Booking::releaseExpired) จะกลับมานับเป็นว่าง
     * param : -
     * return : string
     */
    private static function availableTicketsSql(): string
    {
        return '((SELECT COUNT(*) FROM seat s JOIN zone z ON z.z_id = s.s_zone_id'
            . ' WHERE z.z_concert_id = concert.c_id AND z.zone_type = ? AND s.seat_booking_status = ? AND s.seat_status = ?)'
            . ' + (SELECT COALESCE(SUM(GREATEST(z.capacity - COALESCE(zi.held, 0) - COALESCE(zi.sold, 0), 0)), 0)'
            . ' FROM zone z LEFT JOIN zone_inventory zi ON zi.zi_zone_id = z.z_id'
            . ' WHERE z.z_concert_id = concert.c_id AND z.zone_type = ?))';
    }

    /**
     * availableTicketsBindings()
     * ค่าที่ใช้แทน ? ใน availableTicketsSql()
     * param : -
     * return : array
     */
    private static function availableTicketsBindings(): array
    {
        return [Zone::TYPE_SEATED, Seat::STATUS_AVAILABLE, Seat::SEAT_ACTIVE, Zone::TYPE_STANDING];
    }

    /**
     * scopeWithAvailability(query)
     * เพิ่มคอลัมน์ available_tickets (บัตรที่ยังจองได้) และ zones_count ในผลลัพธ์ ใช้กับหน้ารายการคอนเสิร์ต
     * ให้ displayStatus() ไม่ต้อง query ทีละคอนเสิร์ต
     * param : query
     * return : Builder
     */
    public function scopeWithAvailability(Builder $query): Builder
    {
        if ($query->getQuery()->columns === null) {
            $query->select('concert.*');
        }

        return $query->selectRaw(self::availableTicketsSql() . ' AS available_tickets', self::availableTicketsBindings())
            ->withCount('zones');
    }

    /**
     * scopeWhereDisplayStatus(query, displayStatus)
     * กรองคอนเสิร์ตตามสถานะที่แสดงผล (เงื่อนไขเดียวกับ displayStatus()) ใช้กับตัวกรองหน้าคอนเสิร์ตทั้งหมด
     * param : query, displayStatus (ON_SALE / SCHEDULED / SOLD_OUT)
     * return : Builder
     */
    public function scopeWhereDisplayStatus(Builder $query, string $displayStatus): Builder
    {
        $notEnded = '(concert.event_datetime IS NULL OR concert.event_datetime > NOW())';
        $saleOpen = '(concert.sale_start_datetime IS NULL OR concert.sale_start_datetime <= NOW())'
            . ' AND (concert.sale_end_datetime IS NULL OR concert.sale_end_datetime >= NOW())';
        $soldOut = '(EXISTS (SELECT 1 FROM zone zc WHERE zc.z_concert_id = concert.c_id) AND ' . self::availableTicketsSql() . ' = 0)';
        $bindings = self::availableTicketsBindings();

        return match ($displayStatus) {
            self::STATUS_ON_SALE => $query->where('concert.status', self::STATUS_ON_SALE)
                ->whereRaw("{$notEnded} AND {$saleOpen} AND NOT {$soldOut}", $bindings),
            self::STATUS_SCHEDULED => $query->whereRaw($notEnded)
                ->where(fn (Builder $q) => $q->where('concert.status', self::STATUS_SCHEDULED)
                    ->orWhere(fn (Builder $on) => $on->where('concert.status', self::STATUS_ON_SALE)
                        ->whereRaw('concert.sale_start_datetime > NOW()'))),
            self::STATUS_SOLD_OUT => $query->whereRaw($notEnded)
                ->where(fn (Builder $q) => $q->where('concert.status', self::STATUS_SOLD_OUT)
                    ->orWhere(fn (Builder $on) => $on->where('concert.status', self::STATUS_ON_SALE)
                        ->whereRaw("{$saleOpen} AND {$soldOut}", $bindings))),
            default => $query->where('concert.status', $displayStatus),
        };
    }

    /**
     * availableTickets()
     * จำนวนบัตรที่ยังจองได้ทั้งคอนเสิร์ต (ใช้ค่าจาก scopeWithAvailability ถ้ามี ไม่งั้น query ใหม่ 1 ครั้ง)
     * param : -
     * return : int
     */
    public function availableTickets(): int
    {
        if (array_key_exists('available_tickets', $this->attributes)) {
            return (int) $this->attributes['available_tickets'];
        }

        if ($this->availableTicketsCache === null) {
            $row = DB::selectOne(
                'SELECT ' . self::availableTicketsSql() . ' AS n FROM concert WHERE concert.c_id = ?',
                [...self::availableTicketsBindings(), $this->c_id]
            );
            $this->availableTicketsCache = (int) ($row->n ?? 0);
        }

        return $this->availableTicketsCache;
    }

    /**
     * isSoldOut()
     * ตรวจว่าที่นั่ง/บัตรของทุกโซนถูกจองหมดแล้ว (คอนเสิร์ตที่ยังไม่มีโซนไม่นับว่าขายหมด)
     * param : -
     * return : bool
     */
    public function isSoldOut(): bool
    {
        $zonesCount = $this->zones_count
            ?? ($this->relationLoaded('zones') ? $this->zones->count() : $this->zones()->count());

        return (int) $zonesCount > 0 && $this->availableTickets() === 0;
    }

    /**
     * hasEnded()
     * ตรวจว่าเลยเวลาแสดงแล้ว
     * param : -
     * return : bool
     */
    public function hasEnded(): bool
    {
        return $this->event_datetime !== null && $this->event_datetime->lte(now());
    }

    /**
     * displayStatus()
     * สถานะที่ใช้แสดงผลและตัดสินว่าจองได้ไหม คำนวณจาก status + วันแสดง + ช่วงขายบัตร + บัตรคงเหลือ
     * CANCELLED / DRAFT > เลยวันแสดง (COMPLETED) > SOLD_OUT / SCHEDULED ตาม status >
     * ON_SALE แต่ยังไม่ถึงวันเปิดขาย (SCHEDULED) > เลยวันปิดขาย (SALE_CLOSED) > บัตรหมด (SOLD_OUT) > ON_SALE
     * param : -
     * return : string
     */
    public function displayStatus(): string
    {
        if (in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_DRAFT], true)) {
            return $this->status;
        }
        if ($this->hasEnded()) {
            return self::DISPLAY_COMPLETED;
        }
        if ($this->status !== self::STATUS_ON_SALE) {
            return (string) $this->status;
        }
        if ($this->sale_start_datetime !== null && $this->sale_start_datetime->isFuture()) {
            return self::STATUS_SCHEDULED;
        }
        if ($this->sale_end_datetime !== null && $this->sale_end_datetime->isPast()) {
            return self::DISPLAY_SALE_CLOSED;
        }

        return $this->isSoldOut() ? self::STATUS_SOLD_OUT : self::STATUS_ON_SALE;
    }

    /**
     * canBook()
     * ตรวจว่าลูกค้าจองบัตรคอนเสิร์ตนี้ได้ตอนนี้หรือไม่
     * param : -
     * return : bool
     */
    public function canBook(): bool
    {
        return $this->displayStatus() === self::STATUS_ON_SALE;
    }

    /**
     * bookingBlockedMessage()
     * ข้อความบอกเหตุผลที่จองไม่ได้ ตามสถานะที่แสดงผล
     * param : -
     * return : string
     */
    public function bookingBlockedMessage(): string
    {
        return match ($this->displayStatus()) {
            self::STATUS_SOLD_OUT    => 'บัตรหมดแล้ว',
            self::STATUS_CANCELLED   => 'คอนเสิร์ตนี้ถูกยกเลิก',
            self::DISPLAY_COMPLETED  => 'การแสดงจบแล้ว',
            self::DISPLAY_SALE_CLOSED => 'ปิดการขายบัตรแล้ว',
            default                  => 'ยังไม่เปิดขายบัตร',
        };
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
