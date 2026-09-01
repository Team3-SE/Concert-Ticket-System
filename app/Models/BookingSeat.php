<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * BookingSeat
 * manage BookingSeat entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class BookingSeat extends Model
{
    protected $table = 'booking_seat';
    protected $primaryKey = null;
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;


}
