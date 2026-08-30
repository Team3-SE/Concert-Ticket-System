<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * BookingSeat
 * manage BookingSeat entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class BookingSeat extends Model
{
    use HasUuids;

    protected $table = 'cts_booking_seats';
    protected $primaryKey = 'booking_seat_id';
    public $incrementing = false;
    protected $keyType = 'string';


}
