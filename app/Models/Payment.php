<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Payment
 * manage Payment entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Payment extends Model
{
    use HasUlids;

    protected $table = 'payment';
    protected $primaryKey = 'payment_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;


}
