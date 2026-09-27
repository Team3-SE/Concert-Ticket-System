<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Customer Entity (Subtype of AppUser)
 * Updated : 27/09/2026 – implemented viewTickets(), fixed searchConcert (excludes isDeleted)
 */
class Customer extends AppUser
{
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
     * register(): void
     * สมัครบัญชี Customer (role = CUSTOMER) และบันทึกข้อมูลส่วนตัว
     */
    public function register(): void
    {
        $this->save();
    }

    /**
     * searchConcert(keyword): List<Concert>
     * ค้นหา Concert จากคำค้น ไม่รวม Concert ที่ isDeleted
     */
    public function searchConcert(string $keyword)
    {
        return Concert::query()
            ->where('is_deleted', 0)
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('artist', 'like', "%{$keyword}%")
                  ->orWhere('venue', 'like', "%{$keyword}%");
            })
            ->get();
    }

    /**
     * createBooking(concert, seats, standingItems): Booking
     * สร้าง Booking (Logic หลักอยู่ที่ BookingController)
     */
    public function createBooking($concert, $seats, $standingItems): Booking
    {
        // Delegated to BookingController per specification
        return new Booking();
    }

    /**
     * viewHistory(): List<Booking>
     * ดึงประวัติ Booking ของ Customer
     */
    public function viewHistory()
    {
        return $this->bookings()->get();
    }

    /**
     * viewTickets(): List<Ticket>
     * ดึง E-Ticket ของ Customer ที่เกิดจาก Order หลังชำระเงินสำเร็จ
     */
    public function viewTickets()
    {
        return Ticket::query()
            ->whereHas('order.booking', function ($q) {
                $q->where('customer_id', $this->user_id);
            })
            ->get();
    }

    // ==========================================
    // Relationships
    // ==========================================

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'customer_id', 'user_id');
    }
}
