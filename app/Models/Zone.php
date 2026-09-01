<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Zone
 * manage Zone entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Zone extends Model
{
    use HasUlids;

    protected $table = 'zone';
    protected $primaryKey = 'zone_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;


}
