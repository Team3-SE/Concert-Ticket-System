<?php

/**
 * AnomalyAttachment
 * ไฟล์แนบของรายการความผิดปกติ (ตาราง anomaly_attachment)
 * Author :
 * Created :
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class AnomalyAttachment extends Model
{
    use HasUlids;

    protected $table = 'anomaly_attachment';
    protected $primaryKey = 'attachment_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;


}
