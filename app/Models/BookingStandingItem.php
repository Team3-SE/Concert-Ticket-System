<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingStandingItem extends Model
{
    protected $table = 'booking_standing_item';
    protected $primaryKey = null;
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
