<?php

/**
 * Ticket
 * บัตรเข้างาน (ตาราง ticket) 1 แถว = บัตร 1 ใบ
 * Author :
 * Created :
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

/*
 * schema 3.4.2: มี status (valid / void / refunded) และออกบัตรใหม่แทนใบเดิมได้ (t_replaced_by_ticket_id)
 * slot_no = ลำดับบัตรในคำสั่งซื้อ 1..quantity (บัตรที่ออกใหม่แทนใบเดิมใช้เลขเดิม)
 * active_seat_id / active_slot เป็น generated column + UNIQUE กันบัตร valid ซ้ำที่นั่งหรือซ้ำลำดับ
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticket extends Model
{
    protected $table = 'ticket';
    protected $primaryKey = 't_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    // Status Enumerations (ticket.status ตัวพิมพ์เล็กตาม schema 3.4.2)
    public const STATUS_VALID    = 'valid';
    public const STATUS_VOID     = 'void';
    public const STATUS_REFUNDED = 'refunded';

    // active_seat_id, active_slot เป็น generated column ห้ามใส่ใน fillable (MySQL ไม่ให้ insert/update ค่าเอง)
    protected $fillable = [
        't_order_id',
        't_zone_id',
        't_seat_id',
        'qr_code',
        'is_used',
        'ticket_code',
        'seat_label_snapshot',
        'status',
        'voided_at',
        't_replaced_by_ticket_id',
        'slot_no',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_used'    => 'boolean',
        'slot_no'    => 'integer',
        'voided_at'  => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // Methods from Specification
    // ==========================================

    /**
     * generateQR()
     * สร้าง QR code ของบัตรแล้วบันทึก
     *
     * @return string QR code
     */
    public function generateQR(): string
    {
        $this->qr_code = hash('sha256', $this->t_id . $this->ticket_code . now()->timestamp);
        $this->save();

        return $this->qr_code;
    }

    /**
     * validateTicket()
     * ตรวจว่าบัตรใช้ได้ (สถานะ valid มีโซน) และยังไม่ถูกใช้
     *
     * @return bool true หรือ false ตามที่อธิบายข้างต้น
     */
    public function validateTicket(): bool
    {
        return $this->status === self::STATUS_VALID && !$this->is_used && $this->zone !== null;
    }

    /**
     * markAsUsed()
     * บันทึกว่าบัตรถูกใช้แล้ว
     *
     * @return void ไม่มีค่าส่งกลับ
     */
    public function markAsUsed(): void
    {
        $this->update(['is_used' => true]);
    }

    /**
     * zoneName()
     * ชื่อโซน ณ เวลาที่ซื้อ (schema 3.4.2 เก็บไว้ที่คำสั่งซื้อ user_order.zone_name_snapshot)
     *
     * @return string ข้อความตามที่อธิบายข้างต้น
     */
    public function zoneName(): string
    {
        return (string) ($this->order?->zone_name_snapshot ?? $this->zone?->name);
    }

    /**
     * unitPrice()
     * ราคาต่อใบ ณ เวลาที่ซื้อ (schema 3.4.2 เก็บไว้ที่คำสั่งซื้อ user_order.unit_price_snapshot)
     *
     * @return float ค่าที่ส่งกลับตามที่อธิบายข้างต้น
     */
    public function unitPrice(): float
    {
        return (float) ($this->order?->unit_price_snapshot ?? 0);
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * order()
     * ความสัมพันธ์กับคำสั่งซื้อ
     *
     * @return BelongsTo CustomerOrder
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 't_order_id', 'uo_id');
    }

    /**
     * zone()
     * ความสัมพันธ์กับโซน
     *
     * @return BelongsTo Zone
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 't_zone_id', 'z_id');
    }

    /**
     * seat()
     * ความสัมพันธ์กับที่นั่ง (บัตรยืนจะเป็น null)
     *
     * @return BelongsTo Seat
     */
    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class, 't_seat_id', 's_id');
    }

    /**
     * replacedBy()
     * ความสัมพันธ์กับบัตรใบใหม่ที่ออกแทนใบนี้
     *
     * @return BelongsTo Ticket
     */
    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 't_replaced_by_ticket_id', 't_id');
    }

    /**
     * replaces()
     * ความสัมพันธ์กับบัตรใบเดิมที่ใบนี้ออกมาแทน
     *
     * @return HasOne Ticket
     */
    public function replaces(): HasOne
    {
        return $this->hasOne(Ticket::class, 't_replaced_by_ticket_id', 't_id');
    }

    /**
     * validationLog()
     * ความสัมพันธ์กับประวัติการตรวจบัตร
     *
     * @return HasOne TicketValidationLog
     */
    public function validationLog(): HasOne
    {
        return $this->hasOne(TicketValidationLog::class, 'tvl_ticket_id', 't_id');
    }
}
