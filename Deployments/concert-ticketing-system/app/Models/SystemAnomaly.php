<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * SystemAnomaly
 * manage SystemAnomaly entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class SystemAnomaly extends Model
{
    use HasUuids;

    protected $table = 'cts_system_anomalies';
    protected $primaryKey = 'anomaly_id';
    public $incrementing = false;
    protected $keyType = 'string';


}
