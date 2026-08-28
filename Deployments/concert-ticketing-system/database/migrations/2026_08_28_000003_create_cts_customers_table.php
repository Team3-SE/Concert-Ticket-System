<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateCtsCustomersTable
 * create the cts_customers table for customer role data (inherits cts_users)
 * Author : Squad A (Auth)
 * Created : 28-08-2569
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cts_customers', function (Blueprint $table) {
            $table->comment('à¸‚à¹‰à¸­à¸¡à¸¹à¸¥à¹€à¸žà¸´à¹ˆà¸¡à¹€à¸•à¸´à¸¡à¸ªà¸³à¸«à¸£à¸±à¸šà¸œà¸¹à¹‰à¹ƒà¸Šà¹‰à¸—à¸µà¹ˆà¸¡à¸µà¸šà¸—à¸šà¸²à¸— customer â€” à¸ªà¸²à¸¡à¸²à¸£à¸–à¸ˆà¸­à¸‡à¸•à¸±à¹‹à¸§à¹„à¸”à¹‰');
            $table->uuid('customer_id')->primary()->comment('à¸£à¸«à¸±à¸ªà¸¥à¸¹à¸à¸„à¹‰à¸² (UUID, PK, FK â†’ cts_users.user_id)');
            $table->timestamps();

            $table->foreign('customer_id')->references('user_id')->on('cts_users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cts_customers');
    }
};
