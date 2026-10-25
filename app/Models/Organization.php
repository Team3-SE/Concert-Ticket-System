<?php

/**
 * Organization
 * องค์กรผู้จัดคอนเสิร์ต (ตาราง organization)
 * Author :
 * Created :
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $table = 'organization';
    protected $primaryKey = 'organization_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'name',
        'initials',
        'description',
        'phone',
        'email',
        'facebook',
        'line',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // Methods from Specification
    // ==========================================

    /**
     * getOrganizationInfo()
     * สรุปข้อมูลองค์กร (ชื่อ ชื่อย่อ รายละเอียด และช่องทางติดต่อ)
     * param : -
     * return : string
     */
    public function getOrganizationInfo(): string
    {
        return trim("{$this->name} ({$this->initials}): {$this->description} | phone: {$this->phone} | email: {$this->email} | facebook: {$this->facebook} | line: {$this->line}");
    }

    /**
     * listEvents()
     * ดึงคอนเสิร์ตขององค์กร
     * param : -
     * return : HasMany Concert
     */
    public function listEvents(): HasMany
    {
        return $this->concerts();
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * organizers()
     * ความสัมพันธ์กับผู้จัด (role ORGANIZER) ในองค์กร
     * param : -
     * return : HasMany AppUser
     */
    public function organizers(): HasMany
    {
        return $this->hasMany(AppUser::class, 'organization_id', 'organization_id')
            ->where('role', AppUser::ROLE_ORGANIZER);
    }

    /**
     * staff()
     * ความสัมพันธ์กับพนักงาน (role STAFF) ในองค์กร
     * param : -
     * return : HasMany AppUser
     */
    public function staff(): HasMany
    {
        return $this->hasMany(AppUser::class, 'organization_id', 'organization_id')
            ->where('role', AppUser::ROLE_STAFF);
    }

    /**
     * concerts()
     * ความสัมพันธ์กับคอนเสิร์ตขององค์กร
     * param : -
     * return : HasMany Concert
     */
    public function concerts(): HasMany
    {
        return $this->hasMany(Concert::class, 'organization_id', 'organization_id');
    }
}
