<?php

/**
 * AppUser
 * บัญชีผู้ใช้ทุกประเภท (CUSTOMER / ORGANIZER / STAFF / ADMIN) ในตาราง app_user
 * Author :
 * Created :
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
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
    protected $primaryKey = 'u_id';
    public $timestamps = false;

    // Role Enumerations (ค่าต้องตรงกับ app_user.role ในฐานข้อมูล: ลูกค้าทั่วไปคือ 'USER')
    public const ROLE_CUSTOMER  = 'USER';
    public const ROLE_ORGANIZER = 'ORGANIZER';
    public const ROLE_STAFF     = 'STAFF';
    public const ROLE_ADMIN     = 'ADMIN';

    // Account Status Enumerations (app_user.account_status TINYINT, 1 = ACTIVE, 0 = BANNED)
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
        'u_created_by_app_user_id',
        'u_organization_id',
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
     *
     * @return void ไม่มีค่าส่งกลับ
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
     *
     * @return string password_hash
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * getAuthPasswordName()
     * ระบุชื่อคอลัมน์รหัสผ่านให้ระบบ Auth ของ Laravel
     *
     * @return string 'password_hash'
     */
    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    // ==========================================
    // Methods from Specification
    // ==========================================

    /**
     * login($password)
     * ตรวจสอบสถานะบัญชีและรหัสผ่าน แล้วเข้าสู่ระบบ
     *
     * @param string|null $password รหัสผ่าน
     * @return bool true ถ้าเข้าสู่ระบบสำเร็จ
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
     *
     * @return void ไม่มีค่าส่งกลับ
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
     *
     * @return BelongsTo Organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'u_organization_id', 'org_id');
    }

    /**
     * createdBy()
     * ความสัมพันธ์กับผู้ใช้ที่สร้างบัญชีนี้
     *
     * @return BelongsTo AppUser
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'u_created_by_app_user_id', 'u_id');
    }

    /**
     * auditLogs()
     * ความสัมพันธ์กับประวัติการใช้งานของผู้ใช้
     *
     * @return HasMany AuditLog
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'al_user_id', 'u_id');
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * isActive()
     * ตรวจว่าบัญชีอยู่ในสถานะใช้งานได้ (ACTIVE)
     *
     * @return bool true หรือ false ตามที่อธิบายข้างต้น
     */
    public function isActive(): bool
    {
        return (int) $this->account_status === self::STATUS_ACTIVE;
    }

    /**
     * isSuspended()
     * ตรวจว่าบัญชีถูกระงับ (ชื่อเดิม ให้ใช้ isBanned() แทน)
     *
     * @return bool true หรือ false ตามที่อธิบายข้างต้น
     * @deprecated ใช้ isBanned() แทน
     */
    public function isSuspended(): bool
    {
        return $this->isBanned();
    }

    /**
     * isBanned()
     * ตรวจว่าบัญชีถูกระงับ (BANNED)
     *
     * @return bool true หรือ false ตามที่อธิบายข้างต้น
     */
    public function isBanned(): bool
    {
        return (int) $this->account_status === self::STATUS_BANNED;
    }

    /**
     * isCustomer()
     * ตรวจว่าผู้ใช้มี role เป็น USER (ลูกค้าทั่วไป)
     *
     * @return bool true หรือ false ตามที่อธิบายข้างต้น
     */
    public function isCustomer(): bool
    {
        return strtoupper((string) $this->role) === self::ROLE_CUSTOMER;
    }

    /**
     * isOrganizer()
     * ตรวจว่าผู้ใช้มี role เป็น ORGANIZER
     *
     * @return bool true หรือ false ตามที่อธิบายข้างต้น
     */
    public function isOrganizer(): bool
    {
        return strtoupper((string) $this->role) === self::ROLE_ORGANIZER;
    }

    /**
     * isStaff()
     * ตรวจว่าผู้ใช้มี role เป็น STAFF
     *
     * @return bool true หรือ false ตามที่อธิบายข้างต้น
     */
    public function isStaff(): bool
    {
        return strtoupper((string) $this->role) === self::ROLE_STAFF;
    }

    /**
     * isAdmin()
     * ตรวจว่าผู้ใช้มี role เป็น ADMIN
     *
     * @return bool true หรือ false ตามที่อธิบายข้างต้น
     */
    public function isAdmin(): bool
    {
        return strtoupper((string) $this->role) === self::ROLE_ADMIN;
    }

    /**
     * homeUrl()
     * หน้าแรกของแต่ละ role: หลังเข้าสู่ระบบ และเวลาเปิดหน้าที่ role นี้เข้าไม่ได้ (middleware role)
     *
     * @return string path เช่น '/', '/organizer/dashboard'
     */
    public function homeUrl(): string
    {
        return match (strtoupper((string) $this->role)) {
            self::ROLE_ORGANIZER => route('organizer.dashboard', [], false),
            self::ROLE_ADMIN     => route('admin.users.index', [], false),
            self::ROLE_STAFF     => route('staff.check-in', [], false),
            default              => '/',
        };
    }

    /**
     * areaPrefix()
     * path ขึ้นต้นของส่วนที่ role นี้ใช้งาน (ใช้ตรวจว่าหน้าที่จะพาไปหลัง login อยู่ในส่วนของตัวเองไหม)
     *
     * @return string|null null = ลูกค้า (ใช้หน้าฝั่ง User)
     */
    public function areaPrefix(): ?string
    {
        return match (strtoupper((string) $this->role)) {
            self::ROLE_ORGANIZER => '/organizer',
            self::ROLE_ADMIN     => '/admin',
            self::ROLE_STAFF     => '/staff',
            default              => null,
        };
    }

    /**
     * displayName()
     * ชื่อที่แสดงบนแถบด้านบน: ชื่อ-นามสกุล ถ้าไม่มี (บัญชี Organizer/Staff) ใช้ username แล้วค่อยอีเมล
     *
     * @return string ข้อความตามที่อธิบายข้างต้น
     */
    public function displayName(): string
    {
        $fullName = trim($this->first_name . ' ' . $this->last_name);

        return $fullName !== '' ? $fullName : (string) ($this->username ?: $this->email);
    }
}
