<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Payment extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'public_id',
        'amount',
        'currency',
        'method',
        'status',
        'failure_code',
        'callback_url',
        'return_url',
        'idempotency_key',
        'expires_at',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'expires_at' => 'datetime',
        'paid_at'    => 'datetime',
        'metadata'   => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Payment $model) {
            $model->public_id ??= 'txn_' . strtolower(Str::ulid());
        });
    }

    public function webhookDeliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function responseMetadata(): mixed
    {
        return $this->metadata;
    }

}
