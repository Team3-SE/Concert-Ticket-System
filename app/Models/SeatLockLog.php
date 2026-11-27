<?php

/**
 * SeatLockLog
 * ประวัติการล็อกที่นั่ง (ตาราง seat_lock_log)
 * Author :
 * Created :
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

/*
 * schema 3.4.2 ไม่มีคอลัมน์ status แล้ว: ล็อกที่ยังมีผลคือแถวที่ released_at และ sold_at ยังว่าง
 * (คอลัมน์ active_seat_id เป็น generated column + UNIQUE กันไม่ให้ที่นั่งเดียวกันถูกล็อกซ้อน 2 แถว)
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeatLockLog extends Model
{
    protected $table = 'seat_lock_log';
    protected $primaryKey = 'sll_id';
    public $timestamps = false;

    // active_seat_id เป็น generated column ห้ามใส่ใน fillable (MySQL ไม่ให้ insert/update ค่าเอง)
    protected $fillable = [
        'sll_booking_id',
        'sll_seat_id',
        'locked_at',
        'expires_at',
        'released_at',
        'sold_at',
    ];

    protected $casts = [
        'locked_at'   => 'datetime',
        'expires_at'  => 'datetime',
        'released_at' => 'datetime',
        'sold_at'     => 'datetime',
    ];

    /**
     * scopeActive($query)
     * เฉพาะล็อกที่ยังมีผล (ยังไม่ปล่อยและยังไม่ขาย)
     *
     * @param Builder $query คำค้นหา
     * @return Builder ค่าที่ส่งกลับตามที่อธิบายข้างต้น
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('released_at')->whereNull('sold_at');
    }

    /**
     * isActive()
     * ตรวจว่าล็อกนี้ยังมีผลอยู่
     *
     * @return bool true หรือ false ตามที่อธิบายข้างต้น
     */
    public function isActive(): bool
    {
        return $this->released_at === null && $this->sold_at === null;
    }

    // ─── Relationships ───────────────────────────────────────

    /**
     * booking()
     * ความสัมพันธ์กับการจอง
     *
     * @return BelongsTo Booking
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'sll_booking_id', 'b_id');
    }

    /**
     * seat()
     * ความสัมพันธ์กับที่นั่ง
     *
     * @return BelongsTo Seat
     */
    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class, 'sll_seat_id', 's_id');
    }
}
