<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConcertContact extends Model
{
    use HasUlids;

    protected $table = 'concert_contact';
    protected $primaryKey = 'contact_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['contact_id', 'concert_id', 'contact_type', 'value'];

    public function concert(): BelongsTo
    {
        return $this->belongsTo(Concert::class, 'concert_id', 'concert_id');
    }
}
