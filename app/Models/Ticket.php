<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * Ticket
 * manage Ticket entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 * Updated : 27/09/2026 – fixed incrementing/keyType, fixed validateTicket, added relationships
 */
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
     * generateQR(): String
     * สร้าง QR Code เฉพาะสำหรับ Ticket
     * ticketCode ใช้แสดง/ค้นหาใน UI แยกจาก qrCode
     */
    public function generateQR(): string
    {
        $this->qr_code = hash('sha256', $this->ticket_id . $this->ticket_code . now()->timestamp);
        $this->save();

        return $this->qr_code;
    }

    /**
     * validateTicket(): Boolean
     * ตรวจสอบว่า Ticket ถูกต้อง ตรงกับ Concert (ผ่าน Zone) และยังไม่ถูกใช้
     */
    public function validateTicket(): bool
    {
        return !$this->is_used && $this->zone !== null;
    }

    /**
     * markAsUsed(): void
     * บันทึก Ticket ว่าถูกใช้เข้างานแล้ว โดยเปลี่ยน isUsed เป็น true
     */
    public function markAsUsed(): void
    {
        $this->update(['is_used' => true]);
    }

    // ==========================================
    // Relationships
    // ==========================================

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'order_id', 'order_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_id', 'zone_id');
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class, 'seat_id', 'seat_id');
    }

    public function validationLog(): HasOne
    {
        return $this->hasOne(TicketValidationLog::class, 'ticket_id', 'ticket_id');
    }
}
