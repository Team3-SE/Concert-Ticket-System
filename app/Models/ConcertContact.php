<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ConcertContact extends Model
{
    use HasUuids;

    protected $table = 'concert_contact';
    protected $primaryKey = 'contact_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
