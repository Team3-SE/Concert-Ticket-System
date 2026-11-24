<?php

/**
 * SystemAnomaly
 * ความผิดปกติของระบบที่ตรวจพบ (ตาราง system_anomaly)
 * Author :
 * Created :
 * Updated : 10/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemAnomaly extends Model
{
    protected $table = 'system_anomaly';
    protected $primaryKey = 'sa_id';   // schema 3.4.2: INT AUTO_INCREMENT (เดิมเป็น ULID)
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'sa_booking_id',
        'sa_managed_by_admin_id',
        'sa_caused_by_user_id',
        'type',
        'message',
        'status',
        'resolved_at',
        'acked_at',
        'resolve_reason',
        'anomaly_code',
        'detected_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'acked_at'    => 'datetime',
        'detected_at' => 'datetime',
    ];

    /**
     * booking()
     * ความสัมพันธ์กับการจองที่เกิดความผิดปกติ
     * param : -
     * return : BelongsTo Booking
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'sa_booking_id', 'b_id');
    }

    /**
     * managedBy()
     * ความสัมพันธ์กับผู้ดูแลระบบที่จัดการรายการนี้
     * param : -
     * return : BelongsTo AppUser
     */
    public function managedBy(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'sa_managed_by_admin_id', 'u_id');
    }

    /**
     * causedBy()
     * ความสัมพันธ์กับผู้ใช้ที่ทำให้เกิดความผิดปกติ
     * param : -
     * return : BelongsTo AppUser
     */
    public function causedBy(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'sa_caused_by_user_id', 'u_id');
    }

    /**
     * attachments()
     * ความสัมพันธ์กับไฟล์แนบของรายการนี้
     * param : -
     * return : HasMany AnomalyAttachment
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(AnomalyAttachment::class, 'aa_anomaly_id', 'sa_id');
    }
}
