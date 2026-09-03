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

    // Table & Primary Key
    protected $table = 'company';
    protected $primaryKey = 'company_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    /**
     * ข้อมูลที่สามารถบันทึกได้
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'initials',
        'description',
        'created_at',
    ];

    /**
     * กำหนดชนิดข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * กำหนดค่าเริ่มต้นก่อนสร้างข้อมูล
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }

    /**
     * Company -> EventOrganizer (1 : N)
     *
     * @return HasMany
     */
    public function eventOrganizers(): HasMany
    {
        return $this->hasMany(
            EventOrganizer::class,
            'company_id',
            'company_id'
        );
    }

    /**
     * Company -> Staff (1 : N)
     *
     * @return HasMany
     */
    public function staff(): HasMany
    {
        return $this->hasMany(
            Staff::class,
            'company_id',
            'company_id'
        );
    }

    /**
     * Company -> Concert (1 : N)
     *
     * @return HasMany
     */
    public function concerts(): HasMany
    {
        return $this->hasMany(
            Concert::class,
            'company_id',
            'company_id'
        );
    }
}