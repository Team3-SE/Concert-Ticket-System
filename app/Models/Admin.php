<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Admin Entity (Subtype of AppUser)
 */
class Admin extends AppUser
{
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

    public function manageUser($userID, $action): void
    {
        // To be implemented
    }

    public function viewAnomalies($filters = [])
    {
        // To be implemented
        return SystemAnomaly::all();
    }

    public function acknowledgeAnomaly($anomalyID): void
    {
        // To be implemented
    }

    public function resolveAnomaly($anomalyID, $reason, $files): void
    {
        // To be implemented
    }
}
