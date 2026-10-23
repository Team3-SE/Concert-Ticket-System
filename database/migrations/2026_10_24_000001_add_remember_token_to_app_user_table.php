<?php

/**
 * add_remember_token_to_app_user_table
 * เพิ่มคอลัมน์ remember_token ในตาราง app_user ให้ช่อง "จำฉันไว้" ตอนเข้าสู่ระบบใช้งานได้
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 24/10/2569
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_user', function (Blueprint $table) {
            $table->rememberToken();
        });
    }

    public function down(): void
    {
        Schema::table('app_user', function (Blueprint $table) {
            $table->dropRememberToken();
        });
    }
};
