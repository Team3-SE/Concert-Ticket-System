<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Staff Entity (Subtype of AppUser)
 */
class Staff extends AppUser
{
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
