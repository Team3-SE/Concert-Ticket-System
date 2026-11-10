<?php

/**
 * Organizer
 * ผู้จัดคอนเสิร์ต (AppUser ที่ role = ORGANIZER)
 * Author :
 * Created :
 * Updated : 10/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organizer extends AppUser
{
    /**
     * boot()
     * กำหนดให้ query เฉพาะผู้ใช้ role ORGANIZER และตั้ง role ให้อัตโนมัติเมื่อสร้าง
     * param : -
     * return : void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('role', function (Builder $builder) {
            $builder->where('role', self::ROLE_ORGANIZER);
        });

        static::creating(function ($model) {
            $model->role = self::ROLE_ORGANIZER;
        });
    }

    // ==========================================
    // Methods from Specification
    // ==========================================
    // TODO: Create methods createConcert, defineSeatMap, viewSalesRealTime
    
    // ==========================================
    // Relationships
    // ==========================================

    /**
     * concerts()
     * ความสัมพันธ์กับคอนเสิร์ตขององค์กรที่ผู้จัดสังกัด (concert.c_organization_id = app_user.u_organization_id)
     * param : -
     * return : HasMany Concert
     */
    public function concerts(): HasMany
    {
        return $this->hasMany(Concert::class, 'c_organization_id', 'u_organization_id');
    }

    /**
     * staff()
     * ความสัมพันธ์กับพนักงานที่ผู้จัดสร้าง
     * param : -
     * return : HasMany Staff
     */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'u_created_by_app_user_id', 'u_id');
    }
}
