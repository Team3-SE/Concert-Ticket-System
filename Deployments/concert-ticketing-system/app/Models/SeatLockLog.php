<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * SeatLockLog
 * manage SeatLockLog entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class SeatLockLog extends Model
{
    use HasUuids;

    protected $table = 'cts_seat_lock_logs';
    protected $primaryKey = 'lock_id';
    public $incrementing = false;
    protected $keyType = 'string';


}
