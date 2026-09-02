<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Staff
 * manage Staff entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Staff extends Model
{
    use HasUlids;

    protected $table = 'staff';
    protected $primaryKey = 'staff_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'staff_id', 'company_id', 'created_by_organizer_id', 'username',
    ];

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'staff_id', 'user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function createdByOrganizer(): BelongsTo
    {
        return $this->belongsTo(EventOrganizer::class, 'created_by_organizer_id', 'organizer_id');
    }

    public function staffConcerts(): HasMany
    {
        return $this->hasMany(StaffConcert::class, 'staff_id', 'staff_id');
    }

    public function ticketValidationLogs(): HasMany
    {
        return $this->hasMany(TicketValidationLog::class, 'staff_id', 'staff_id');
    }

    public function scanTicketQR(string $qrCode): bool
    {
        $ticket = Ticket::query()->where('qr_code', $qrCode)->first();
        return $ticket !== null && $ticket->validateTicket();
    }

}
