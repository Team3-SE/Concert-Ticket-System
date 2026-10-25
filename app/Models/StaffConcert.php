<?php

/**
 * StaffConcert
 * การมอบหมายพนักงานให้คอนเสิร์ต (ตาราง staff_concert)
 * Author :
 * Created :
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffConcert extends Model
{
    protected $table = 'staff_concert';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['staff_id', 'concert_id'];

    /**
     * staff()
     * ความสัมพันธ์กับพนักงาน
     * param : -
     * return : BelongsTo Staff
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }

    /**
     * concert()
     * ความสัมพันธ์กับคอนเสิร์ต
     * param : -
     * return : BelongsTo Concert
     */
    public function concert(): BelongsTo
    {
        return $this->belongsTo(Concert::class, 'concert_id', 'concert_id');
    }

}
