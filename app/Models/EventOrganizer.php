<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * EventOrganizer
 * manage EventOrganizer entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class EventOrganizer extends Model
{
    use HasUlids;

    protected $table = 'event_organizer';
    protected $primaryKey = 'organizer_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['organizer_id', 'company_id', 'email', 'username'];

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'organizer_id', 'user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function concerts(): HasMany
    {
        return $this->hasMany(Concert::class, 'organizer_id', 'organizer_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'created_by_organizer_id', 'organizer_id');
    }


}
