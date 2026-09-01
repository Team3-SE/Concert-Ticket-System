<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * SeatLockLog
 * manage SeatLockLog entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class SeatLockLog extends Model
{
    use HasUlids;

    protected $table = 'seat_lock_log';
    protected $primaryKey = 'lock_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;


}
