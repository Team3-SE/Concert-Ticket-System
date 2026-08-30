<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateCtsUsersTable
 * create the cts_users table for all user accounts (base entity for all roles)
 * Author : Squad A (Auth)
 * Created : 28-08-2569
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cts_users', function (Blueprint $table) {
            $table->comment('à¸œà¸¹à¹‰à¹ƒà¸Šà¹‰à¸‡à¸²à¸™à¸—à¸±à¹‰à¸‡à¸«à¸¡à¸”à¹ƒà¸™à¸£à¸°à¸šà¸š (base entity à¸‚à¸­à¸‡ role à¸•à¹ˆà¸²à¸‡à¹†)');
            $table->uuid('user_id')->primary()->comment('à¸£à¸«à¸±à¸ªà¸›à¸£à¸°à¸ˆà¸³à¸œà¸¹à¹‰à¹ƒà¸Šà¹‰ (UUID, PK)');
            $table->string('name')->comment('à¸Šà¸·à¹ˆà¸­-à¸™à¸²à¸¡à¸ªà¸à¸¸à¸¥à¸‚à¸­à¸‡à¸œà¸¹à¹‰à¹ƒà¸Šà¹‰');
            $table->string('email')->unique()->comment('à¸­à¸µà¹€à¸¡à¸¥à¸œà¸¹à¹‰à¹ƒà¸Šà¹‰ (unique, à¹ƒà¸Šà¹‰à¸ªà¸³à¸«à¸£à¸±à¸š login)');
            $table->string('phone', 20)->nullable()->comment('à¹€à¸šà¸­à¸£à¹Œà¹‚à¸—à¸£à¸¨à¸±à¸žà¸—à¹Œà¸œà¸¹à¹‰à¹ƒà¸Šà¹‰ (optional)');
            $table->string('password_hash')->comment('à¸£à¸«à¸±à¸ªà¸œà¹ˆà¸²à¸™à¸—à¸µà¹ˆà¸œà¹ˆà¸²à¸™ hashing (bcrypt) â€” à¸«à¹‰à¸²à¸¡à¹€à¸à¹‡à¸š plaintext à¹€à¸”à¹‡à¸”à¸‚à¸²à¸”');
            $table->enum('role', ['customer', 'organizer', 'staff', 'admin'])->comment('à¸šà¸—à¸šà¸²à¸—à¸‚à¸­à¸‡à¸œà¸¹à¹‰à¹ƒà¸Šà¹‰: customer, organizer, staff, admin');
            $table->boolean('is_banned')->default(false)->comment('à¸ªà¸–à¸²à¸™à¸°à¸–à¸¹à¸à¹à¸šà¸™: true=à¸–à¸¹à¸à¹à¸šà¸™ à¸«à¹‰à¸²à¸¡ login, false=à¸›à¸à¸•à¸´');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cts_users');
    }
};
