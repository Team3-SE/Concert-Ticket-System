<?php

/**
 * ZoneInventory
 * ตัวนับบัตรของโซนยืน (ตาราง zone_inventory) 1 แถวต่อ 1 โซน
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 10/11/2569
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

/*
 * held = จำนวนที่จองไว้รอชำระเงิน, sold = จำนวนที่ชำระเงินแล้ว
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoneInventory extends Model
{
    protected $table = 'zone_inventory';
    protected $primaryKey = 'zi_zone_id';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;   // updated_at อัปเดตเองด้วย ON UPDATE CURRENT_TIMESTAMP

    protected $fillable = [
        'zi_zone_id',
        'held',
        'sold',
    ];

    protected $casts = [
        'held'       => 'integer',
        'sold'       => 'integer',
        'updated_at' => 'datetime',
    ];

    /**
     * lockForZone($zoneId)
     * ดึงแถวตัวนับของโซนพร้อมล็อกแถว (SELECT ... FOR UPDATE) ถ้ายังไม่มีให้สร้างใหม่ ต้องเรียกภายใน DB::transaction
     *
     * @param int $zoneId รหัสโซน
     * @return ZoneInventory ค่าที่ส่งกลับตามที่อธิบายข้างต้น
     */
    public static function lockForZone(int $zoneId): self
    {
        $inventory = static::query()->whereKey($zoneId)->lockForUpdate()->first();

        if ($inventory === null) {
            static::query()->insertOrIgnore(['zi_zone_id' => $zoneId, 'held' => 0, 'sold' => 0]);
            $inventory = static::query()->whereKey($zoneId)->lockForUpdate()->firstOrFail();
        }

        return $inventory;
    }

    /**
     * available($capacity)
     * จำนวนบัตรที่ยังจองได้ = ความจุโซน - held - sold
     *
     * @param int $capacity ความจุ
     * @return int ค่าจำนวนตามที่อธิบายข้างต้น
     */
    public function available(int $capacity): int
    {
        return max(0, $capacity - (int) $this->held - (int) $this->sold);
    }

    /**
     * hold($quantity)
     * จองบัตรไว้รอชำระเงิน (held + quantity)
     *
     * @param int $quantity จำนวน
     * @return void ไม่มีค่าส่งกลับ
     */
    public function hold(int $quantity): void
    {
        $this->held = (int) $this->held + $quantity;
        $this->save();
    }

    /**
     * release($quantity)
     * คืนบัตรที่จองไว้เมื่อการจองหมดเวลาหรือถูกยกเลิก (held - quantity ไม่ต่ำกว่า 0)
     *
     * @param int $quantity จำนวน
     * @return void ไม่มีค่าส่งกลับ
     */
    public function release(int $quantity): void
    {
        $this->held = max(0, (int) $this->held - $quantity);
        $this->save();
    }

    /**
     * sell($quantity)
     * ย้ายบัตรจากที่จองไว้เป็นขายแล้วหลังชำระเงินสำเร็จ (held - quantity, sold + quantity)
     *
     * @param int $quantity จำนวน
     * @return void ไม่มีค่าส่งกลับ
     */
    public function sell(int $quantity): void
    {
        $this->held = max(0, (int) $this->held - $quantity);
        $this->sold = (int) $this->sold + $quantity;
        $this->save();
    }

    /**
     * zone()
     * ความสัมพันธ์กับโซน
     *
     * @return BelongsTo Zone
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zi_zone_id', 'z_id');
    }
}
