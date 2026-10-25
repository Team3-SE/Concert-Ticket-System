<?php

/**
 * NamingTemplate
 * แม่แบบการตั้งชื่อที่นั่ง (ตาราง naming_template)
 * Author :
 * Created :
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NamingTemplate extends Model
{
    protected $table = 'naming_template';
    protected $primaryKey = 'template_id';
    public $timestamps = false; // Based on schema created_at is default

    protected $fillable = [
        'organizer_id', 'template_name', 'pattern_type', 'prefix', 'custom_items', 'created_at'
    ];

    protected $casts = [
        'custom_items' => 'array',
        'created_at' => 'datetime',
    ];

    // TODO: Add relationships if needed
}
