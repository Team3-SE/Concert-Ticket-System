<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateCtsConcertsTable
 * create the cts_concerts table for concert event records
 * Author : Squad B (Concert)
 * Created : 28-08-2569
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cts_concerts', function (Blueprint $table) {
            $table->comment('à¸‚à¹‰à¸­à¸¡à¸¹à¸¥à¸„à¸­à¸™à¹€à¸ªà¸´à¸£à¹Œà¸•à¸—à¸µà¹ˆà¸ˆà¸±à¸”à¸‚à¸¶à¹‰à¸™à¹ƒà¸™à¸£à¸°à¸šà¸š');
            $table->uuid('concert_id')->primary()->comment('à¸£à¸«à¸±à¸ªà¸›à¸£à¸°à¸ˆà¸³à¸„à¸­à¸™à¹€à¸ªà¸´à¸£à¹Œà¸• (UUID, PK)');
            $table->uuid('company_id')->comment('à¸£à¸«à¸±à¸ªà¸šà¸£à¸´à¸©à¸±à¸—à¸œà¸¹à¹‰à¸ˆà¸±à¸”à¸„à¸­à¸™à¹€à¸ªà¸´à¸£à¹Œà¸• (FK â†’ cts_companies.company_id)');
            $table->uuid('organizer_id')->comment('à¸£à¸«à¸±à¸ª organizer à¸œà¸¹à¹‰à¸£à¸±à¸šà¸œà¸´à¸”à¸Šà¸­à¸š (FK â†’ cts_event_organizers.organizer_id)');
            $table->string('name')->comment('à¸Šà¸·à¹ˆà¸­à¸„à¸­à¸™à¹€à¸ªà¸´à¸£à¹Œà¸•');
            $table->string('artist')->comment('à¸Šà¸·à¹ˆà¸­à¸¨à¸´à¸¥à¸›à¸´à¸™/à¸§à¸‡à¸”à¸™à¸•à¸£à¸µà¸—à¸µà¹ˆà¹à¸ªà¸”à¸‡');
            $table->dateTime('date_time')->comment('à¸§à¸±à¸™à¹à¸¥à¸°à¹€à¸§à¸¥à¸²à¸—à¸µà¹ˆà¸ˆà¸±à¸”à¸„à¸­à¸™à¹€à¸ªà¸´à¸£à¹Œà¸•');
            $table->string('venue')->comment('à¸ªà¸–à¸²à¸™à¸—à¸µà¹ˆà¸ˆà¸±à¸”à¸„à¸­à¸™à¹€à¸ªà¸´à¸£à¹Œà¸•');
            $table->timestamps();

            $table->foreign('company_id')->references('company_id')->on('cts_companies')->onDelete('restrict');
            $table->foreign('organizer_id')->references('organizer_id')->on('cts_event_organizers')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cts_concerts');
    }
};
