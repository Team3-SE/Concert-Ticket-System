<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateCtsAdminsTable
 * create the cts_admins table for admin role data
 * Author : Squad F (Admin/Infra)
 * Created : 28-08-2569
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cts_admins', function (Blueprint $table) {
            $table->comment('à¸‚à¹‰à¸­à¸¡à¸¹à¸¥à¹€à¸žà¸´à¹ˆà¸¡à¹€à¸•à¸´à¸¡à¸ªà¸³à¸«à¸£à¸±à¸šà¸œà¸¹à¹‰à¹ƒà¸Šà¹‰à¸—à¸µà¹ˆà¸¡à¸µà¸šà¸—à¸šà¸²à¸— admin â€” à¸ˆà¸±à¸”à¸à¸²à¸£à¸£à¸°à¸šà¸šà¹à¸¥à¸°à¸œà¸¹à¹‰à¹ƒà¸Šà¹‰');
            $table->uuid('admin_id')->primary()->comment('à¸£à¸«à¸±à¸ª admin (UUID, PK, FK â†’ cts_users.user_id)');
            $table->timestamps();

            $table->foreign('admin_id')->references('user_id')->on('cts_users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cts_admins');
    }
};
