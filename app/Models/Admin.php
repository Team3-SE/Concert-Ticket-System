<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Admin
 * manage Admin entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Admin extends Model
{
    use HasUuids;

    protected $table = 'admin';
    protected $primaryKey = 'admin_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;


}
