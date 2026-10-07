<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(base_path('concert_ticketing_schema_v3.1.0.sql'));
        
        // Remove schema creation and specific database references to respect .env
        $sql = preg_replace('/CREATE SCHEMA IF NOT EXISTS `concert_ticketing`[^;]*;/', '', $sql);
        $sql = preg_replace('/USE `concert_ticketing`\s*;/', '', $sql);
        $sql = str_replace('`concert_ticketing`.', '', $sql);
        
        DB::unprepared($sql);
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $tables = [
            'ticket_validation_log',
            'ticket',
            'staff_concert',
            'seat_lock_log',
            'user_order',
            'payment',
            'booking_seat',
            'seat',
            'zone_seat_pattern',
            'naming_template',
            'zone',
            'audit_log',
            'anomaly_attachment',
            'system_anomaly',
            'booking',
            'concert',
            'app_user',
            'organization',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};

