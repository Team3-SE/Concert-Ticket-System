<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * TicketValidationLog
 * manage TicketValidationLog entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class TicketValidationLog extends Model
{
    use HasUuids;

    protected $table = 'ticket_validation_log';
    protected $primaryKey = 'validation_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;


}
