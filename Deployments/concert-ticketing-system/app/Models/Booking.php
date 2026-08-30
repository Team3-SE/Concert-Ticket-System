<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Booking
 * manage Booking entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Booking extends Model
{
    use HasUuids;

    protected $table = 'cts_bookings';
    protected $primaryKey = 'booking_id';
    public $incrementing = false;
    protected $keyType = 'string';


}
