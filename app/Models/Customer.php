<?php

/**
 * Customer
 * ลูกค้า (AppUser ที่ role = USER)
 * Author :
 * Created :
 * Updated : 10/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Customer extends AppUser
{
    /**
     * boot()
     * กำหนดให้ query เฉพาะผู้ใช้ role USER และตั้ง role ให้อัตโนมัติเมื่อสร้าง
     * param : -
     * return : void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('role', function (Builder $builder) {
            $builder->where('role', self::ROLE_CUSTOMER);
        });

        static::creating(function ($model) {
            $model->role = self::ROLE_CUSTOMER;
        });
    }

    // ==========================================
    // Methods from Specification
    // ==========================================

    /**
     * register()
     * สมัครบัญชีลูกค้า (บันทึกข้อมูลผู้ใช้)
     * param : -
     * return : void
     */
    public function register(): void
    {
        $this->save();
    }

    /**
     * searchConcert(keyword)
     * ค้นหาคอนเสิร์ตจากชื่อ ศิลปิน หรือสถานที่
     * param : keyword
     * return : Collection ของ Concert
     */
    public function searchConcert(string $keyword)
    {
        // Concert ใช้ SoftDeletes: คอนเสิร์ตที่ถูกลบ (deleted_at ไม่ว่าง) จะไม่ถูกดึงมาเอง
        return Concert::query()
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('artist', 'like', "%{$keyword}%")
                  ->orWhere('venue', 'like', "%{$keyword}%");
            })
            ->get();
    }

    /**
     * createBooking(concert, seats, standingItems)
     * สร้างการจอง (logic หลักอยู่ที่ BookingController)
     * param : concert, seats, standingItems
     * return : Booking
     */
    public function createBooking($concert, $seats, $standingItems): Booking
    {
        // Delegated to BookingController per specification
        return new Booking();
    }

    /**
     * viewHistory()
     * ดึงประวัติการจองของลูกค้า
     * param : -
     * return : Collection ของ Booking
     */
    public function viewHistory()
    {
        return $this->bookings()->get();
    }

    /**
     * viewTickets()
     * ดึงบัตรทั้งหมดของลูกค้า
     * param : -
     * return : Collection ของ Ticket
     */
    public function viewTickets()
    {
        return Ticket::query()
            ->whereHas('order.booking', function ($q) {
                $q->where('b_user_id', $this->u_id);
            })
            ->get();
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * bookings()
     * ความสัมพันธ์กับการจองของลูกค้า
     * param : -
     * return : HasMany Booking
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'b_user_id', 'u_id');
    }
}
