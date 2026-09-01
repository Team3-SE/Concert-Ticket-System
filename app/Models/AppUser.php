<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class AppUser extends Authenticatable
{
    use HasFactory, Notifiable, HasUlids;

    protected $table = 'app_user';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function getAuthPassword()
    {
        return $this->password_hash;
    }
}
