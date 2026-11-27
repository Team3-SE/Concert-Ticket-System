<?php

/**
 * AnomalyAttachment
 * ไฟล์แนบของรายการความผิดปกติ (ตาราง anomaly_attachment)
 * Author :
 * Created :
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnomalyAttachment extends Model
{
    protected $table = 'anomaly_attachment';
    protected $primaryKey = 'aa_id';   // schema 3.4.2: INT AUTO_INCREMENT (เดิมเป็น ULID)
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'aa_anomaly_id',
        'file_path',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * anomaly()
     * ความสัมพันธ์กับรายการความผิดปกติ
     *
     * @return BelongsTo SystemAnomaly
     */
    public function anomaly(): BelongsTo
    {
        return $this->belongsTo(SystemAnomaly::class, 'aa_anomaly_id', 'sa_id');
    }
}
