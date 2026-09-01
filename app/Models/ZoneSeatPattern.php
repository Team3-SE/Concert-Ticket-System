<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ZoneSeatPattern extends Model
{
    use HasUlids;

    protected $table = 'zone_seat_pattern';
    protected $primaryKey = 'pattern_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
