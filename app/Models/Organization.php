<?php

/**
 * Organization
 * องค์กรผู้จัดคอนเสิร์ต (ตาราง organization)
 * Author :
 * Created :
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $table = 'organization';
    protected $primaryKey = 'org_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    // schema 3.4.2: ช่องทางติดต่อ (phone, email, facebook, line) ย้ายไปอยู่ที่ตาราง concert
    protected $fillable = [
        'name',
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
     * สรุปข้อมูลองค์กร (schema 3.4.2 เหลือแค่ชื่อองค์กร)
     *
     * @return string ข้อความตามที่อธิบายข้างต้น
     */
    public function getOrganizationInfo(): string
    {
        return (string) $this->name;
    }

    /**
     * listEvents()
     * ดึงคอนเสิร์ตขององค์กร
     *
     * @return HasMany Concert
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
     *
     * @return HasMany AppUser
     */
    public function organizers(): HasMany
    {
        return $this->hasMany(AppUser::class, 'u_organization_id', 'org_id')
            ->where('role', AppUser::ROLE_ORGANIZER);
    }

    /**
     * staff()
     * ความสัมพันธ์กับพนักงาน (role STAFF) ในองค์กร
     *
     * @return HasMany AppUser
     */
    public function staff(): HasMany
    {
        return $this->hasMany(AppUser::class, 'u_organization_id', 'org_id')
            ->where('role', AppUser::ROLE_STAFF);
    }

    /**
     * concerts()
     * ความสัมพันธ์กับคอนเสิร์ตขององค์กร
     *
     * @return HasMany Concert
     */
    public function concerts(): HasMany
    {
        return $this->hasMany(Concert::class, 'c_organization_id', 'org_id');
    }
}
