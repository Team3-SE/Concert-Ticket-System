<?php

/**
 * Staff
 * พนักงานตรวจบัตร (AppUser ที่ role = STAFF)
 * Author :
 * Created :
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Staff extends AppUser
{
    /**
     * boot()
     * กำหนดให้ query เฉพาะผู้ใช้ role STAFF และตั้ง role ให้อัตโนมัติเมื่อสร้าง
     *
     * @return void ไม่มีค่าส่งกลับ
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('role', function (Builder $builder) {
            $builder->where('role', self::ROLE_STAFF);
        });

        static::creating(function ($model) {
            $model->role = self::ROLE_STAFF;
        });
    }

    // ==========================================
    // Methods from Specification
    // ==========================================
    // TODO: Create method scanTicketQR
}
