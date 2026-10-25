<?php

/**
 * AppUser
 * บัญชีผู้ใช้ทุกประเภท (CUSTOMER / ORGANIZER / STAFF / ADMIN) ในตาราง app_user
 * Author :
 * Created :
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AppUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'app_user';
    protected $primaryKey = 'user_id';
    public $timestamps = false;

    // Role Enumerations (ค่าต้องตรงกับ app_user.role ในฐานข้อมูล: ลูกค้าทั่วไปคือ 'USER' เหมือน default ของ schema v3.1.0)
    public const ROLE_CUSTOMER  = 'USER';
    public const ROLE_ORGANIZER = 'ORGANIZER';
    public const ROLE_STAFF     = 'STAFF';
    public const ROLE_ADMIN     = 'ADMIN';

    // Account Status Enumerations (schema v3.1.0: app_user.account_status TINYINT, 1 = ACTIVE, 0 = BANNED)
    public const STATUS_ACTIVE = 1;
    public const STATUS_BANNED = 0;

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
        'account_status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * boot()
     * ตั้งค่าเริ่มต้นตอนสร้างผู้ใช้ (created_at และ account_status = ACTIVE)
     * param : -
     * return : void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
            if ($model->account_status === null) {
                $model->account_status = self::STATUS_ACTIVE;
            }
        });
    }

    /**
     * getAuthPassword()
     * ส่งรหัสผ่านที่ hash แล้วให้ระบบ Auth ของ Laravel ใช้ตรวจสอบ
     * param : -
     * return : string password_hash
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * getAuthPasswordName()
     * ระบุชื่อคอลัมน์รหัสผ่านให้ระบบ Auth ของ Laravel
     * param : -
     * return : string 'password_hash'
     */
    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    // ==========================================
    // Methods from Specification
    // ==========================================

    /**
     * login(password)
     * ตรวจสอบสถานะบัญชีและรหัสผ่าน แล้วเข้าสู่ระบบ
     * param : password
     * return : bool – true ถ้าเข้าสู่ระบบสำเร็จ
     */
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

    /**
     * logout()
     * ออกจากระบบ และยกเลิก session / token
     * param : -
     * return : void
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
    // Relationships
    // ==========================================

    /**
     * organization()
     * ความสัมพันธ์กับองค์กรที่ผู้ใช้สังกัด
     * param : -
     * return : BelongsTo Organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id', 'organization_id');
    }

    /**
     * createdBy()
     * ความสัมพันธ์กับผู้ใช้ที่สร้างบัญชีนี้
     * param : -
     * return : BelongsTo AppUser
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'created_by_user_id', 'user_id');
    }

    /**
     * auditLogs()
     * ความสัมพันธ์กับประวัติการใช้งานของผู้ใช้
     * param : -
     * return : HasMany AuditLog
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id', 'user_id');
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * isActive()
     * ตรวจว่าบัญชีอยู่ในสถานะใช้งานได้ (ACTIVE)
     * param : -
     * return : bool
     */
    public function isActive(): bool
    {
        return (int) $this->account_status === self::STATUS_ACTIVE;
    }

    /**
     * isSuspended()
     * ตรวจว่าบัญชีถูกระงับ (ชื่อเดิม ให้ใช้ isBanned() แทน)
     * param : -
     * return : bool
     * @deprecated ใช้ isBanned() แทน
     */
    public function isSuspended(): bool
    {
        return $this->isBanned();
    }

    /**
     * isBanned()
     * ตรวจว่าบัญชีถูกระงับ (BANNED)
     * param : -
     * return : bool
     */
    public function isBanned(): bool
    {
        return (int) $this->account_status === self::STATUS_BANNED;
    }

    /**
     * isCustomer()
     * ตรวจว่าผู้ใช้มี role เป็น USER (ลูกค้าทั่วไป)
     * param : -
     * return : bool
     */
    public function isCustomer(): bool
    {
        return strtoupper((string) $this->role) === self::ROLE_CUSTOMER;
    }

    /**
     * isOrganizer()
     * ตรวจว่าผู้ใช้มี role เป็น ORGANIZER
     * param : -
     * return : bool
     */
    public function isOrganizer(): bool
    {
        return strtoupper((string) $this->role) === self::ROLE_ORGANIZER;
    }

    /**
     * isStaff()
     * ตรวจว่าผู้ใช้มี role เป็น STAFF
     * param : -
     * return : bool
     */
    public function isStaff(): bool
    {
        return strtoupper((string) $this->role) === self::ROLE_STAFF;
    }

    /**
     * isAdmin()
     * ตรวจว่าผู้ใช้มี role เป็น ADMIN
     * param : -
     * return : bool
     */
    public function isAdmin(): bool
    {
        return strtoupper((string) $this->role) === self::ROLE_ADMIN;
    }
}
