<?php

/**
 * SystemAnomaly
 * ความผิดปกติของระบบที่ตรวจพบ (ตาราง system_anomaly)
 * Author :
 * Created :
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class SystemAnomaly extends Model
{
    use HasUlids;

    protected $table = 'system_anomaly';
    protected $primaryKey = 'anomaly_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;


}
