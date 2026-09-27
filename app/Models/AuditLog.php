<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AuditLog
 * manage AuditLog entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 * Updated : 27/09/2026 – fixed column mapping (created_at, metadata_details)
 */
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
     * getDetails(): String
     * ดึงรายละเอียด Audit Log เช่น ผู้ดำเนินการ Action เวลา IP Address และ metadataDetails
     */
    public function getDetails(): string
    {
        return trim("action: {$this->action} | at: {$this->created_at} | ip: {$this->ip_address} | metadata: " . json_encode($this->metadata_details));
    }

    // ==========================================
    // Relationships
    // ==========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id', 'user_id');
    }
}
