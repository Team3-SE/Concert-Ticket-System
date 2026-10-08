<?php

/**
 * AuditLog
 * ประวัติการใช้งานระบบ (ตาราง audit_log)
 * Author :
 * Created :
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_log';
    protected $primaryKey = 'log_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
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
     * สรุปรายละเอียดของ log (action เวลา IP และ metadata)
     * param : -
     * return : string
     */
    public function getDetails(): string
    {
        return trim("action: {$this->action} | at: {$this->created_at} | ip: {$this->ip_address} | metadata: " . json_encode($this->metadata_details));
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * user()
     * ความสัมพันธ์กับผู้ใช้ที่ทำรายการ
     * param : -
     * return : BelongsTo AppUser
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id', 'user_id');
    }
}
