<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Zone
 * manage Zone entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Zone extends Model
{
    use HasUlids;

    protected $table = 'zone';
    protected $primaryKey = 'zone_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['zone_id', 'concert_id', 'name', 'zone_type', 'price', 'capacity', 'color'];

    protected $casts = [
        'price' => 'decimal:2',
        'capacity' => 'integer',
    ];

    public function concert(): BelongsTo
    {
        return $this->belongsTo(Concert::class, 'concert_id', 'concert_id');
    }

    public function seatPattern(): HasOne
    {
        return $this->hasOne(ZoneSeatPattern::class, 'zone_id', 'zone_id');
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class, 'zone_id', 'zone_id');
    }

    public function bookingStandingItems(): HasMany
    {
        return $this->hasMany(BookingStandingItem::class, 'zone_id', 'zone_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'zone_id', 'zone_id');
    }

    public function getAvailableSeats(): HasMany
    {
        return $this->seats()->where('status', 'AVAILABLE');
    }

    public function getAvailableCapacity(): int
    {
        if ($this->zone_type !== 'STANDING') {
            return $this->getAvailableSeats()->count();
        }

        $reserved = $this->bookingStandingItems()
            ->whereHas('booking', function ($query) {
                $query->whereIn('status', ['PENDING_PAYMENT', 'CONFIRMED']);
            })
            ->sum('quantity');

        return max(0, $this->capacity - $reserved);
    }

}
