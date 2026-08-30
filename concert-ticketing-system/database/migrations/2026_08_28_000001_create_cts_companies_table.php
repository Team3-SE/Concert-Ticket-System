<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateCtsCompaniesTable
 * create the cts_companies table for storing concert organizer companies
 * Author : Squad F (Infra)
 * Created : 28-08-2569
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cts_companies', function (Blueprint $table) {
            $table->comment('à¸šà¸£à¸´à¸©à¸±à¸—à¸œà¸¹à¹‰à¸ˆà¸±à¸”à¸„à¸­à¸™à¹€à¸ªà¸´à¸£à¹Œà¸•');
            $table->uuid('company_id')->primary()->comment('à¸£à¸«à¸±à¸ªà¸›à¸£à¸°à¸ˆà¸³à¸šà¸£à¸´à¸©à¸±à¸— (UUID, PK)');
            $table->string('name')->comment('à¸Šà¸·à¹ˆà¸­à¹€à¸•à¹‡à¸¡à¸‚à¸­à¸‡à¸šà¸£à¸´à¸©à¸±à¸—');
            $table->string('initials', 10)->comment('à¸Šà¸·à¹ˆà¸­à¸¢à¹ˆà¸­à¸šà¸£à¸´à¸©à¸±à¸— à¹€à¸Šà¹ˆà¸™ GMM, BEC');
            $table->text('description')->nullable()->comment('à¸„à¸³à¸­à¸˜à¸´à¸šà¸²à¸¢à¸šà¸£à¸´à¸©à¸±à¸—à¹‚à¸”à¸¢à¸¢à¹ˆà¸­');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cts_companies');
    }
};
