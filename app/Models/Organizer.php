<?php

/**
 * Organizer
 * ผู้จัดคอนเสิร์ต (AppUser ที่ role = ORGANIZER)
 * Author :
 * Created :
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
     * ความสัมพันธ์กับคอนเสิร์ตของผู้จัด
     * param : -
     * return : HasMany Concert
     */
    public function concerts(): HasMany
    {
        return $this->hasMany(Concert::class, 'organizer_id', 'user_id');
    }

    /**
     * staff()
     * ความสัมพันธ์กับพนักงานที่ผู้จัดสร้าง
     * param : -
     * return : HasMany Staff
     */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'created_by_user_id', 'user_id');
    }
}
