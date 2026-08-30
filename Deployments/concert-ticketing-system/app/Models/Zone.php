<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Zone
 * manage Zone entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Zone extends Model
{
    use HasUuids;

    protected $table = 'cts_zones';
    protected $primaryKey = 'zone_id';
    public $incrementing = false;
    protected $keyType = 'string';


}
