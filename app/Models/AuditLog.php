<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AuditLog
 * manage AuditLog entity and its relationships
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

    protected $fillable = [
        'log_id', 'user_id', 'action', 'timestamp', 'ip_address',
    ];

    protected $casts = ['timestamp' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id', 'user_id');
    }

    public function getDetails(): string
    {
        return trim("{$this->action} at {$this->timestamp} from {$this->ip_address}");
    }

}
