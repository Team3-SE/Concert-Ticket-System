<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Concert
 * manage Concert entity and its relationships
 * Author : AI Agent
 * Created : 30/08/2026
 * Updated : 27/09/2026 – fixed organization FK, renamed getDetails()
 */
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

    /**
     * getDetails(): ConcertInfo
     * ดึงรายละเอียดของ Concert รวม concertCode, ศิลปิน, สถานที่, วันเวลา,
     * ช่วงเวลาขายบัตร, สถานะ และข้อมูลสื่อ
     */
    public function getDetails(): string
    {
        return "{$this->name} (code: {$this->concert_code}) by {$this->artist} at {$this->venue}, {$this->address} on {$this->event_datetime} | status: {$this->status}";
    }

    // ==========================================
    // Relationships
    // ==========================================

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id', 'organization_id');
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class, 'concert_id', 'concert_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'concert_id', 'concert_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'zone_id', 'concert_id');
    }

    public function staffConcerts(): HasMany
    {
        return $this->hasMany(StaffConcert::class, 'concert_id', 'concert_id');
    }
}
