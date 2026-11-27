<?php

/**
 * AuditLog
 * ประวัติการใช้งานระบบ (ตาราง audit_log)
 * Author :
 * Created :
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_log';
    protected $primaryKey = 'al_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    // schema 3.4.2: คอลัมน์ action (int) เปลี่ยนเป็น type (varchar)
    protected $fillable = [
        'al_user_id',
        'type',
        'created_at',
        'ip_address',
        'metadata_details',
    ];

    protected $casts = [
        'created_at'       => 'datetime',
        'metadata_details' => 'array',
    ];

    // ==========================================
    // Methods from Specification
    // ==========================================

    /**
     * getDetails()
     * สรุปรายละเอียดของ log (ประเภท เวลา IP และ metadata)
     *
     * @return string ข้อความตามที่อธิบายข้างต้น
     */
    public function getDetails(): string
    {
        return trim("type: {$this->type} | at: {$this->created_at} | ip: {$this->ip_address} | metadata: " . json_encode($this->metadata_details));
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * user()
     * ความสัมพันธ์กับผู้ใช้ที่ทำรายการ
     *
     * @return BelongsTo AppUser
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'al_user_id', 'u_id');
    }
}
