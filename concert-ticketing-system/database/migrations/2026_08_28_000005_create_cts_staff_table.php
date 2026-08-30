<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateCtsStaffTable
 * create the cts_staff table for staff role data
 * Author : Squad A (Auth)
 * Created : 28-08-2569
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cts_staff', function (Blueprint $table) {
            $table->comment('à¸‚à¹‰à¸­à¸¡à¸¹à¸¥à¹€à¸žà¸´à¹ˆà¸¡à¹€à¸•à¸´à¸¡à¸ªà¸³à¸«à¸£à¸±à¸šà¸œà¸¹à¹‰à¹ƒà¸Šà¹‰à¸—à¸µà¹ˆà¸¡à¸µà¸šà¸—à¸šà¸²à¸— staff â€” à¸—à¸³à¸«à¸™à¹‰à¸²à¸—à¸µà¹ˆà¸•à¸£à¸§à¸ˆà¸ªà¸­à¸šà¸•à¸±à¹‹à¸§');
            $table->uuid('staff_id')->primary()->comment('à¸£à¸«à¸±à¸ª staff (UUID, PK, FK â†’ cts_users.user_id)');
            $table->uuid('company_id')->comment('à¸£à¸«à¸±à¸ªà¸šà¸£à¸´à¸©à¸±à¸—à¸—à¸µà¹ˆ staff à¸ªà¸±à¸‡à¸à¸±à¸” (FK â†’ cts_companies.company_id)');
            $table->timestamps();

            $table->foreign('staff_id')->references('user_id')->on('cts_users')->onDelete('cascade');
            $table->foreign('company_id')->references('company_id')->on('cts_companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cts_staff');
    }
};
