<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Admin
 * manage Admin entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Admin extends Model
{
    use HasUlids;

    protected $table = 'admin';
    protected $primaryKey = 'admin_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['admin_id', 'email', 'username'];

    public function manageUser(string $userId, string $action): void
    {
        $user = AppUser::query()->where('user_id', $userId)->firstOrFail();
        $user->update(['account_status' => strtoupper($action) === 'SUSPEND' ? 'SUSPENDED' : 'ACTIVE']);
    }

    public function viewAnomalies(array $filters = []): HasMany
    {
        return $this->systemAnomalies()->when($filters, function ($query) use ($filters) {
            $query->where($filters);
        });
    }

    public function acknowledgeAnomaly(string $anomalyId): void
    {
        $this->systemAnomalies()->where('anomaly_id', $anomalyId)->update(['status' => 'INVESTIGATING']);
    }

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'admin_id', 'user_id');
    }

    public function systemAnomalies(): HasMany
    {
        return $this->hasMany(SystemAnomaly::class, 'reviewed_by_admin_id', 'admin_id');
    }


}
