<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Ticket
 * manage Ticket entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Ticket extends Model
{
    use HasUlids;

    protected $table = 'ticket';
    protected $primaryKey = 'ticket_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;


}
