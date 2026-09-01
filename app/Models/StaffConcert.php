<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * StaffConcert
 * manage StaffConcert entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class StaffConcert extends Model
{
    use HasUuids;

    protected $table = 'staff_concert';
    protected $primaryKey = null;
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;


}
