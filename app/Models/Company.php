<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Company
 * manage Company entity and its relationships
 * Author : AI Agent
 * Created : 28/08/2026
 */
class Company extends Model
{
    use HasUlids;

    protected $table = 'company';
    protected $primaryKey = 'company_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;


}
