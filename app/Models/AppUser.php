<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * AppUser
 * manage AppUser entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class AppUser extends Authenticatable
{
    use HasFactory, Notifiable, HasUlids;

    // Table & Primary Key
    protected $table = 'app_user';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    // Role Enumerations
    public const ROLE_CUSTOMER = 'CUSTOMER';
    public const ROLE_ORGANIZER = 'ORGANIZER';
    public const ROLE_STAFF = 'STAFF';
    public const ROLE_ADMIN = 'ADMIN';

    // Account Status Enumerations
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'password_hash',
        'role',
        'account_status',
        'created_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Boot function for model events.
     */
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

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Get the name of the password attribute for the user.
     *
     * @return string
     */
    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    // ==========================================
    // Entity Methods (Specification)
    // ==========================================

    /**
     * login(password = null)
     * ตรวจสอบข้อมูลเข้าสู่ระบบและสถานะบัญชีของผู้ใช้ ก่อนอนุญาตให้ใช้งาน
     *
     * @param string|null $password
     * @return bool
     */
    public function login(?string $password = null): bool
    {
        // ตรวจสอบสถานะบัญชีว่าใช้งานได้ (ACTIVE) หรือไม่
        if (!$this->isActive()) {
            return false;
        }

        // หากมีการระบุรหัสผ่าน ให้ตรวจสอบความถูกต้องของรหัสผ่าน
        if ($password !== null && !Hash::check($password, $this->password_hash)) {
            return false;
        }

        // ทำการ Login เข้าสู่ระบบผ่าน Laravel Auth Guard
        Auth::login($this);

        return true;
    }

    /**
     * logout()
     * ออกจากระบบและยกเลิก Session หรือ Token ที่ใช้งานอยู่
     *
     * @return void
     */
    public function logout(): void
    {
        Auth::logout();

        if (session()) {
            session()->invalidate();
            session()->regenerateToken();
        }
    }

    // ==========================================
    // Subtype Relationships (1 : 0..1)
    // ==========================================

    /**
     * AppUser -> Customer (1 : 0..1)
     *
     * @return HasOne
     */
    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class, 'customer_id', 'user_id');
    }

    /**
     * AppUser -> EventOrganizer (1 : 0..1)
     *
     * @return HasOne
     */
    public function eventOrganizer(): HasOne
    {
        return $this->hasOne(EventOrganizer::class, 'organizer_id', 'user_id');
    }

    /**
     * AppUser -> Staff (1 : 0..1)
     *
     * @return HasOne
     */
    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class, 'staff_id', 'user_id');
    }

    /**
     * AppUser -> Admin (1 : 0..1)
     *
     * @return HasOne
     */
    public function admin(): HasOne
    {
        return $this->hasOne(Admin::class, 'admin_id', 'user_id');
    }

    // ==========================================
    // Relationships (1 : N)
    // ==========================================

    /**
     * AppUser -> AuditLog (1 : N)
     *
     * @return HasMany
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id', 'user_id');
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * ตรวจสอบว่าบัญชีอยู่ในสถานะ ACTIVE หรือไม่
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return strtoupper((string) $this->account_status) === self::STATUS_ACTIVE;
    }

    /**
     * ตรวจสอบว่าบัญชีถูกระงับ (SUSPENDED) หรือไม่
     *
     * @return bool
     */
    public function isSuspended(): bool
    {
        return strtoupper((string) $this->account_status) === self::STATUS_SUSPENDED;
    }

    /**
     * ดึงข้อมูลโปรไฟล์ตามประเภทย่อย (Subtype) ของ Role
     *
     * @return Customer|EventOrganizer|Staff|Admin|null
     */
    public function getProfile()
    {
        return match (strtoupper((string) $this->role)) {
            self::ROLE_CUSTOMER => $this->customer,
            self::ROLE_ORGANIZER => $this->eventOrganizer,
            self::ROLE_STAFF => $this->staff,
            self::ROLE_ADMIN => $this->admin,
            default => null,
        };
    }

    /**
     * Helper ตรวจสอบ Role
     */
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
