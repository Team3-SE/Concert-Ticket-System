<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Support\Str;

/**
 * Ticket
 * manage Ticket entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Ticket extends Model
{
    use HasUlids;

    protected $table = 'ticket';
    protected $primaryKey = 'ticket_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'concert_id',
        'zone_id',
        'seat_id',
        'qr_code',
        'is_used',
        'ticket_code',
        'zone_name_snapshot',
        'seat_label_snapshot',
        'unit_price_snapshot',
    ];

    /**
     * generateQR()
     * Generate a unique QR code value for this Ticket and persist it.
     * param : none
     * return : String
     */
    public function generateQR()
    {
        $this->qr_code = (string) Str::uuid();
        $this->save();

        return $this->qr_code;
    }

    /**
     * validateTicket()
     * Check that the Ticket is valid, matches its Concert, and can still be used to enter the event.
     * param : none
     * return : Boolean
     */
    public function validateTicket()
    {
        return !$this->is_used && $this->concert !== null;
    }

    /**
     * markAsUsed()
     * Mark the Ticket as used to enter the event.
     * param : none
     * return : void
     */
    public function markAsUsed(): void
    {
        $this->is_used = true;
        $this->save();
    }

    public function concert()
    {
        return $this->belongsTo(Concert::class, 'concert_id', 'concert_id');
    }
}
