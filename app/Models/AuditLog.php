<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

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


}
