<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Company
 * manage Company entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Company extends Model
{
    use HasUlids;

    protected $table = 'company';
    protected $primaryKey = 'company_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['company_id', 'name', 'initials', 'description', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function organizers(): HasMany
    {
        return $this->hasMany(EventOrganizer::class, 'company_id', 'company_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'company_id', 'company_id');
    }

    public function concerts(): HasMany
    {
        return $this->hasMany(Concert::class, 'company_id', 'company_id');
    }

    public function getCompanyInfo(): string
    {
        return trim("{$this->name} ({$this->initials}): {$this->description}");
    }

    public function listEvents(): HasMany
    {
        return $this->concerts();
    }


}
