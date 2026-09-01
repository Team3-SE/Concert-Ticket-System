<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CustomerOrder extends Model
{
    use HasUuids;

    protected $table = 'customer_order';
    protected $primaryKey = 'order_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
