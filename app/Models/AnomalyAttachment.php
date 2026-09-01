<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * AnomalyAttachment
 * manage AnomalyAttachment entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class AnomalyAttachment extends Model
{
    use HasUuids;

    protected $table = 'anomaly_attachment';
    protected $primaryKey = 'attachment_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;


}
