<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Organization
 * manage Organization entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 * Updated : 27/09/2026 – renamed from Company to Organization to match DB schema
 */
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
     * getOrganizationInfo(): String
     * ดึงและสรุปข้อมูลของบริษัท เช่น ชื่อ ชื่อย่อ รายละเอียดบริษัท และช่องทางติดต่อ
     */
    public function getOrganizationInfo(): string
    {
        return trim("{$this->name} ({$this->initials}): {$this->description} | phone: {$this->phone} | email: {$this->email} | facebook: {$this->facebook} | line: {$this->line}");
    }

    /**
     * listEvents(): List<Concert>
     * ดึงรายการ Concert ทั้งหมดที่อยู่ภายใต้บริษัทนี้
     */
    public function listEvents(): HasMany
    {
        return $this->concerts();
    }

    // ==========================================
    // Relationships
    // ==========================================

    public function organizers(): HasMany
    {
        return $this->hasMany(AppUser::class, 'organization_id', 'organization_id')
            ->where('role', AppUser::ROLE_ORGANIZER);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(AppUser::class, 'organization_id', 'organization_id')
            ->where('role', AppUser::ROLE_STAFF);
    }

    public function concerts(): HasMany
    {
        return $this->hasMany(Concert::class, 'organization_id', 'organization_id');
    }
}
