<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Ticket
 * manage Ticket entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Ticket extends Model
{
    use HasUuids;

    protected $table = 'cts_tickets';
    protected $primaryKey = 'ticket_id';
    public $incrementing = false;
    protected $keyType = 'string';


}
