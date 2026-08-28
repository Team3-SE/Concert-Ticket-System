<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateCtsEventOrganizersTable
 * create the cts_event_organizers table for organizer role data
 * Author : Squad A (Auth)
 * Created : 28-08-2569
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cts_event_organizers', function (Blueprint $table) {
            $table->comment('à¸‚à¹‰à¸­à¸¡à¸¹à¸¥à¹€à¸žà¸´à¹ˆà¸¡à¹€à¸•à¸´à¸¡à¸ªà¸³à¸«à¸£à¸±à¸šà¸œà¸¹à¹‰à¹ƒà¸Šà¹‰à¸—à¸µà¹ˆà¸¡à¸µà¸šà¸—à¸šà¸²à¸— organizer â€” à¸ˆà¸±à¸”à¸à¸²à¸£à¸„à¸­à¸™à¹€à¸ªà¸´à¸£à¹Œà¸•à¹„à¸”à¹‰');
            $table->uuid('organizer_id')->primary()->comment('à¸£à¸«à¸±à¸ª organizer (UUID, PK, FK â†’ cts_users.user_id)');
            $table->uuid('company_id')->comment('à¸£à¸«à¸±à¸ªà¸šà¸£à¸´à¸©à¸±à¸—à¸—à¸µà¹ˆ organizer à¸ªà¸±à¸‡à¸à¸±à¸” (FK â†’ cts_companies.company_id)');
            $table->timestamps();

            $table->foreign('organizer_id')->references('user_id')->on('cts_users')->onDelete('cascade');
            $table->foreign('company_id')->references('company_id')->on('cts_companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cts_event_organizers');
    }
};
