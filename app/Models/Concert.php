<?php

/**
 * Concert
 * คอนเสิร์ต (ตาราง concert)
 * Author :
 * Created :
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
