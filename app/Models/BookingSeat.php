<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BookingSeat
 * manage BookingSeat entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class BookingSeat extends Model
{
    protected $table = 'booking_seat';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'booking_id', 'seat_id', 'zone_name_snapshot',
        'seat_label_snapshot', 'unit_price_snapshot',
    ];

    protected $casts = ['unit_price_snapshot' => 'decimal:2'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class, 'seat_id', 'seat_id');
    }

}
