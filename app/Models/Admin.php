<?php

/**
 * Admin
 * ผู้ดูแลระบบ (AppUser ที่ role = ADMIN)
 * Author :
 * Created :
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Admin extends AppUser
{
    /**
     * boot()
     * กำหนดให้ query เฉพาะผู้ใช้ role ADMIN และตั้ง role ให้อัตโนมัติเมื่อสร้าง
     * param : -
     * return : void
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
     * manageUser(userID, action)
     * จัดการบัญชีผู้ใช้ตาม action ที่กำหนด (ยังไม่ได้พัฒนา)
     * param : userID, action
     * return : void
     */
    public function manageUser($userID, $action): void
    {
        // To be implemented
    }

    /**
     * viewAnomalies(filters)
     * ดูรายการความผิดปกติของระบบ (ตอนนี้ยังไม่กรองตาม filters)
     * param : filters
     * return : Collection ของ SystemAnomaly
     */
    public function viewAnomalies($filters = [])
    {
        // To be implemented
        return SystemAnomaly::all();
    }

    /**
     * acknowledgeAnomaly(anomalyID)
     * รับทราบรายการความผิดปกติ (ยังไม่ได้พัฒนา)
     * param : anomalyID
     * return : void
     */
    public function acknowledgeAnomaly($anomalyID): void
    {
        // To be implemented
    }

    /**
     * resolveAnomaly(anomalyID, reason, files)
     * ปิดรายการความผิดปกติพร้อมเหตุผลและไฟล์แนบ (ยังไม่ได้พัฒนา)
     * param : anomalyID, reason, files
     * return : void
     */
    public function resolveAnomaly($anomalyID, $reason, $files): void
    {
        // To be implemented
    }
}
