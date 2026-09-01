<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Booking
 * manage Booking entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Booking extends Model
{
    use HasUlids;

    protected $table = 'booking';
    protected $primaryKey = 'booking_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;


}
