<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * AuditLog
 * manage AuditLog entity and its relationships — บันทึก audit trail สำหรับตรวจสอบย้อนหลัง
 * Author : AI Agent
 * Created : 28/08/2026
 */
class AuditLog extends Model
{
    use HasUlids;

    protected $table = 'audit_log';
    protected $primaryKey = 'log_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'timestamp',
        'ip_address',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    /**
     * uniqueIds()
     * specify which columns should receive ULID values — only log_id.
     * param : none
     * return : array
     */
    public function uniqueIds(): array
    {
        return ['log_id'];
    }

    /**
     * user()
     * retrieve the AppUser who performed this action.
     * param : none
     * return : \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id', 'user_id');
    }

    /**
     * getDetails()
     * ดึงรายละเอียด Audit Log เช่น ผู้ดำเนินการ Action เวลา และ IP Address เพื่อใช้ตรวจสอบย้อนหลัง
     * param : none
     * return : String
     */
    public function getDetails(): string
    {
        $userName = 'System';

        if ($this->user_id && $this->relationLoaded('user') && $this->user) {
            $userName = $this->user->role . ' (ID: ' . $this->user_id . ')';
        } elseif ($this->user_id) {
            $relatedUser = $this->user;
            if ($relatedUser) {
                $userName = $relatedUser->role . ' (ID: ' . $this->user_id . ')';
            } else {
                $userName = 'Unknown User (ID: ' . $this->user_id . ')';
            }
        }

        $formattedTimestamp = $this->timestamp
            ? $this->timestamp->format('Y-m-d H:i:s')
            : 'N/A';

        $ipAddress = $this->ip_address ?? 'N/A';

        return "ผู้ดำเนินการ: {$userName} | Action: {$this->action} | เวลา: {$formattedTimestamp} | IP: {$ipAddress}";
    }
}
