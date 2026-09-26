<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Organizer Entity (Subtype of AppUser)
 */
class Organizer extends AppUser
{
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

    public function createConcert($details): Concert
    {
        // To be implemented
        return new Concert();
    }

    public function defineSeatMap($concert, $mapDetails): void
    {
        // To be implemented
    }

    public function viewSalesRealTime($concert)
    {
        // To be implemented
        return [];
    }
    
    // ==========================================
    // Relationships
    // ==========================================

    public function concerts(): HasMany
    {
        return $this->hasMany(Concert::class, 'organizer_id', 'user_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'created_by_user_id', 'user_id');
    }
}
