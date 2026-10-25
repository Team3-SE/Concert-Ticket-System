<?php

/**
 * Ticket
 * บัตรเข้างาน (ตาราง ticket)
 * Author :
 * Created :
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Ticket extends Model
{
    protected $table = 'ticket';
    protected $primaryKey = 'ticket_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'zone_id',
        'seat_id',
        'qr_code',
        'is_used',
        'ticket_code',
        'quantity',
        'zone_name_snapshot',
        'seat_label_snapshot',
        'unit_price_snapshot',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_used'            => 'boolean',
        'quantity'           => 'integer',
        'unit_price_snapshot'=> 'decimal:2',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
    ];

    // ==========================================
    // Methods from Specification
    // ==========================================

    /**
     * generateQR()
     * สร้าง QR code ของบัตรแล้วบันทึก
     * param : -
     * return : string QR code
     */
    public function generateQR(): string
    {
        $this->qr_code = hash('sha256', $this->ticket_id . $this->ticket_code . now()->timestamp);
        $this->save();

        return $this->qr_code;
    }

    /**
     * validateTicket()
     * ตรวจว่าบัตรถูกต้อง (มีโซน) และยังไม่ถูกใช้
     * param : -
     * return : bool
     */
    public function validateTicket(): bool
    {
        return !$this->is_used && $this->zone !== null;
    }

    /**
     * markAsUsed()
     * บันทึกว่าบัตรถูกใช้แล้ว
     * param : -
     * return : void
     */
    public function markAsUsed(): void
    {
        $this->update(['is_used' => true]);
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * order()
     * ความสัมพันธ์กับคำสั่งซื้อ
     * param : -
     * return : BelongsTo CustomerOrder
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'order_id', 'order_id');
    }

    /**
     * zone()
     * ความสัมพันธ์กับโซน
     * param : -
     * return : BelongsTo Zone
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_id', 'zone_id');
    }

    /**
     * seat()
     * ความสัมพันธ์กับที่นั่ง (บัตรยืนจะเป็น null)
     * param : -
     * return : BelongsTo Seat
     */
    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class, 'seat_id', 'seat_id');
    }

    /**
     * validationLog()
     * ความสัมพันธ์กับประวัติการตรวจบัตร
     * param : -
     * return : HasOne TicketValidationLog
     */
    public function validationLog(): HasOne
    {
        return $this->hasOne(TicketValidationLog::class, 'ticket_id', 'ticket_id');
    }
}
