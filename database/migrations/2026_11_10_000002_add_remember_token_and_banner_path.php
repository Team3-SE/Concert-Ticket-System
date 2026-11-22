<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * up()
     * เพิ่ม remember_token ท้ายตาราง app_user และ banner_path ต่อจาก poster_path ในตาราง concert
     * param : -
     * return : void
     */
    public function up(): void
    {
        Schema::table('app_user', function (Blueprint $table) {
            $table->rememberToken()->comment('token ของช่อง "จำฉันไว้" ตอนเข้าสู่ระบบ (Laravel)');
        });

        Schema::table('concert', function (Blueprint $table) {
            $table->string('banner_path')->nullable()->after('poster_path')->comment('path รูปแบนเนอร์แนวนอนของคอนเสิร์ต');
        });
    }

    /**
     * down()
     * ลบ 2 คอลัมน์ที่เพิ่มใน up()
     * param : -
     * return : void
     */
    public function down(): void
    {
        Schema::table('concert', function (Blueprint $table) {
            $table->dropColumn('banner_path');
        });

        Schema::table('app_user', function (Blueprint $table) {
            $table->dropRememberToken();
        });
    }
};
