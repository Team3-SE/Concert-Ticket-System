<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TicketValidationLog
 * manage TicketValidationLog entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class TicketValidationLog extends Model
{
    protected $table = 'ticket_validation_log';
    protected $primaryKey = 'validation_id';
    public $timestamps = false;

    protected $fillable = ['validation_id', 'ticket_id', 'staff_id', 'validation_time', 'result'];

    protected $casts = ['validation_time' => 'datetime', 'result' => 'boolean'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'ticket_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }

}
