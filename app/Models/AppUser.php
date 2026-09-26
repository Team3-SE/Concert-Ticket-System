<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * AppUser
 * manage AppUser entity and its relationships (Supertype)
 */
class AppUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'app_user';
    protected $primaryKey = 'user_id';
    public $timestamps = false;

    // Role Enumerations
    public const ROLE_CUSTOMER = 'CUSTOMER';
    public const ROLE_ORGANIZER = 'ORGANIZER';
    public const ROLE_STAFF = 'STAFF';
    public const ROLE_ADMIN = 'ADMIN';

    // Account Status Enumerations
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';

    protected $fillable = [
        'email',
        'password_hash',
        'role',
        'account_status',
        'first_name',
        'last_name',
        'phone',
        'username',
        'created_by_user_id',
        'organization_id',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
            if (empty($model->account_status)) {
                $model->account_status = self::STATUS_ACTIVE;
            }
        });
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    public function login(?string $password = null): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($password !== null && !Hash::check($password, $this->password_hash)) {
            return false;
        }

        Auth::login($this);
        return true;
    }

    public function logout(): void
    {
        Auth::logout();
        if (session()) {
            session()->invalidate();
            session()->regenerateToken();
        }
    }

    // ==========================================
    // Relationships
    // ==========================================

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id', 'user_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id', 'organization_id');
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    public function isActive(): bool
    {
        return strtoupper((string) $this->account_status) === self::STATUS_ACTIVE;
    }

    public function isSuspended(): bool
    {
        return strtoupper((string) $this->account_status) === self::STATUS_SUSPENDED;
    }

    public function isCustomer(): bool
    {
        return strtoupper((string) $this->role) === self::ROLE_CUSTOMER;
    }

    public function isOrganizer(): bool
    {
        return strtoupper((string) $this->role) === self::ROLE_ORGANIZER;
    }

    public function isStaff(): bool
    {
        return strtoupper((string) $this->role) === self::ROLE_STAFF;
    }

    public function isAdmin(): bool
    {
        return strtoupper((string) $this->role) === self::ROLE_ADMIN;
    }
}
