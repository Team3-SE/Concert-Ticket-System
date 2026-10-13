<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WebhookDelivery extends Model
{
    protected $table = 'webhook_deliveries';

    protected $fillable = [
        'payment_id',
        'event_id',
        'event',
        'payload',
        'attempts',
        'last_status_code',
        'last_error',
        'delivered_at',
    ];

    protected $casts = [
        'payload'      => 'array',
        'delivered_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (WebhookDelivery $model) {
            $model->event_id ??= 'evt_' . strtolower(Str::ulid());
        });
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
