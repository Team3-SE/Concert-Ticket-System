<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Customer Entity (Subtype of AppUser)
 */
class Customer extends AppUser
{
    protected static function boot()
    {
        parent::boot();

        // Global scope to only fetch CUSTOMER roles
        static::addGlobalScope('role', function (Builder $builder) {
            $builder->where('role', self::ROLE_CUSTOMER);
        });

        // Auto-assign role when creating a new Customer
        static::creating(function ($model) {
            $model->role = self::ROLE_CUSTOMER;
        });
    }

    // ==========================================
    // Methods from Specification
    // ==========================================

    public function register(): void
    {
        $this->save();
    }

    public function searchConcert(string $keyword)
    {
        return Concert::query()
            ->where('name', 'like', "%{$keyword}%")
            ->orWhere('artist', 'like', "%{$keyword}%")
            ->orWhere('venue', 'like', "%{$keyword}%")
            ->get();
    }

    public function createBooking($concert, $seats, $standingItems): Booking
    {
        // To be implemented in controller/service
        return new Booking();
    }

    public function viewHistory()
    {
        return $this->bookings()->get();
    }

    public function viewTickets()
    {
        // To be implemented via CustomerOrder -> Ticket
        return [];
    }

    // ==========================================
    // Relationships
    // ==========================================

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'customer_id', 'user_id');
    }
}
