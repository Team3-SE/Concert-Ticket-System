<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Customer
 * manage Customer entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Customer extends Model
{
    use HasUlids;

    protected $table = 'customer';
    protected $primaryKey = 'customer_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'email',
        'phone',
        'first_name',
        'last_name',
    ];

    public function searchConcert(string $keyword): Builder
    {
        return Concert::query()
            ->where('name', 'like', "%{$keyword}%")
            ->orWhere('artist', 'like', "%{$keyword}%")
            ->orWhere('venue', 'like', "%{$keyword}%");
    }

    public function register(): void
    {
        $this->save();
    }

    // ─── Relationships ───────────────────────────────────────

    /**
     * Customer belongs to AppUser (supertype)
     * customer_id references app_user.user_id
     */
    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'customer_id', 'user_id');
    }

    /**
     * Customer has many Bookings
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'customer_id', 'customer_id');
    }
}
