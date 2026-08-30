<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Chargeback
 * manage Chargeback entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Chargeback extends Model
{
    use HasUuids;

    protected $table = 'cts_chargebacks';
    protected $primaryKey = 'chargeback_id';
    public $incrementing = false;
    protected $keyType = 'string';


}
