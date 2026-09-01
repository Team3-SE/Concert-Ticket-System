<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * EventOrganizer
 * manage EventOrganizer entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class EventOrganizer extends Model
{
    use HasUlids;

    protected $table = 'event_organizer';
    protected $primaryKey = 'organizer_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;


}
