<?php

/**
 * StaffConcert
 * การมอบหมายพนักงานให้คอนเสิร์ต (ตาราง staff_concert)
 * Author :
 * Created :
 * Updated : 10/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffConcert extends Model
{
    protected $table = 'staff_concert';
    public $incrementing = false;
    public $timestamps = false;

    // Status Enumerations (staff_concert.status TINYINT)
    public const STATUS_ACTIVE  = 1;
    public const STATUS_REVOKED = 0;

    protected $fillable = [
        'sc_staff_id',
        'sc_concert_id',
        'assigned_at',
        'status',
        'revoked_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'revoked_at'  => 'datetime',
        'status'      => 'integer',
    ];

    /**
     * staff()
     * ความสัมพันธ์กับพนักงาน
     * param : -
     * return : BelongsTo Staff
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'sc_staff_id', 'u_id');
    }

    /**
     * concert()
     * ความสัมพันธ์กับคอนเสิร์ต
     * param : -
     * return : BelongsTo Concert
     */
    public function concert(): BelongsTo
    {
        return $this->belongsTo(Concert::class, 'sc_concert_id', 'c_id');
    }
}
