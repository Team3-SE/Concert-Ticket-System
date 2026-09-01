<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Customer
 * manage Customer entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Customer extends Model
{
    use HasUlids;

    protected $table = 'customer';
    protected $primaryKey = 'customer_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;


}
