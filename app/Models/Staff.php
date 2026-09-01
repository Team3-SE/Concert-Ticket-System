<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Staff
 * manage Staff entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Staff extends Model
{
    use HasUlids;

    protected $table = 'staff';
    protected $primaryKey = 'staff_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;


}
