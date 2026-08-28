<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * User
 * manage user authentication and base profile
 * Author : AI Agent
 * Created : 28/08/2026
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids;
}
