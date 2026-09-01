<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ZoneSeatPattern extends Model
{
    use HasUuids;

    protected $table = 'zone_seat_pattern';
    protected $primaryKey = 'pattern_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
