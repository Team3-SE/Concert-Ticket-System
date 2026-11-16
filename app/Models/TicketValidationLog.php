<?php

/**
 * TicketValidationLog
 * ประวัติการตรวจบัตร (ตาราง ticket_validation_log)
 * Author :
 * Created :
 * Updated : 10/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketValidationLog extends Model
{
    protected $table = 'ticket_validation_log';
    protected $primaryKey = 'tvl_id';
    public $timestamps = false;

    protected $fillable = ['tvl_ticket_id', 'tvl_staff_id', 'validation_time', 'result'];

    protected $casts = ['validation_time' => 'datetime', 'result' => 'boolean'];

    /**
     * ticket()
     * ความสัมพันธ์กับบัตร
     * param : -
     * return : BelongsTo Ticket
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'tvl_ticket_id', 't_id');
    }

    /**
     * staff()
     * ความสัมพันธ์กับพนักงานที่ตรวจบัตร
     * param : -
     * return : BelongsTo Staff
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'tvl_staff_id', 'u_id');
    }
}
