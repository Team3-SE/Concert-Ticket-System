<?php

/**
 * Admin
 * ผู้ดูแลระบบ (AppUser ที่ role = ADMIN)
 * Author :
 * Created :
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Admin extends AppUser
{
    /**
     * boot()
     * กำหนดให้ query เฉพาะผู้ใช้ role ADMIN และตั้ง role ให้อัตโนมัติเมื่อสร้าง
     *
     * @return void ไม่มีค่าส่งกลับ
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('role', function (Builder $builder) {
            $builder->where('role', self::ROLE_ADMIN);
        });

        static::creating(function ($model) {
            $model->role = self::ROLE_ADMIN;
        });
    }

    // ==========================================
    // Methods from Specification
    // ==========================================

    /**
     * manageUser($userID, $action)
     * จัดการบัญชีผู้ใช้ตาม action ที่กำหนด (ยังไม่ได้พัฒนา)
     *
     * @param mixed $userID รหัสผู้ใช้
     * @param mixed $action การกระทำที่ต้องการ
     * @return void ไม่มีค่าส่งกลับ
     */
    public function manageUser($userID, $action): void
    {
        // To be implemented
    }

    /**
     * viewAnomalies($filters)
     * ดูรายการความผิดปกติของระบบ (ตอนนี้ยังไม่กรองตาม filters)
     *
     * @param array $filters เงื่อนไขการกรอง
     * @return Collection ของ SystemAnomaly
     */
    public function viewAnomalies($filters = [])
    {
        // To be implemented
        return SystemAnomaly::all();
    }

    /**
     * acknowledgeAnomaly($anomalyID)
     * รับทราบรายการความผิดปกติ (ยังไม่ได้พัฒนา)
     *
     * @param mixed $anomalyID รหัสรายการผิดปกติ
     * @return void ไม่มีค่าส่งกลับ
     */
    public function acknowledgeAnomaly($anomalyID): void
    {
        // To be implemented
    }

    /**
     * resolveAnomaly($anomalyID, $reason, $files)
     * ปิดรายการความผิดปกติพร้อมเหตุผลและไฟล์แนบ (ยังไม่ได้พัฒนา)
     *
     * @param mixed $anomalyID รหัสรายการผิดปกติ
     * @param mixed $reason เหตุผล
     * @param mixed $files ไฟล์ที่อัปโหลด
     * @return void ไม่มีค่าส่งกลับ
     */
    public function resolveAnomaly($anomalyID, $reason, $files): void
    {
        // To be implemented
    }
}
