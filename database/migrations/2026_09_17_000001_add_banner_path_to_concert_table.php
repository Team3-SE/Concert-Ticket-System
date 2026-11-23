<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มคอลัมน์ banner_path ในตาราง concert
 * สำหรับเก็บ path รูปแบนเนอร์แนวนอนของคอนเสิร์ต
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('concert', function (Blueprint $table) {
            $table->string('banner_path')->nullable()->after('poster_path');
        });
    }

    public function down(): void
    {
        Schema::table('concert', function (Blueprint $table) {
            $table->dropColumn('banner_path');
        });
    }
};

