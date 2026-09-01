<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Seat
 * manage Seat entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Seat extends Model
{
    use HasUlids;

    protected $table = 'seat';
    protected $primaryKey = 'seat_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;


}
