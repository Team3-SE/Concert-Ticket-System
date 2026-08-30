<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateCtsStaffConcertsTable
 * create the cts_staff_concerts junction table for staff assigned to concerts
 * Author : Squad E (Ticket/Staff)
 * Created : 28-08-2569
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cts_staff_concerts', function (Blueprint $table) {
            $table->comment('à¸•à¸²à¸£à¸²à¸‡ junction à¸à¸³à¸«à¸™à¸” staff à¸„à¸™à¹„à¸«à¸™à¸£à¸±à¸šà¸œà¸´à¸”à¸Šà¸­à¸šà¸•à¸£à¸§à¸ˆà¸•à¸±à¹‹à¸§à¸„à¸­à¸™à¹€à¸ªà¸´à¸£à¹Œà¸•à¹„à¸«à¸™');
            $table->uuid('staff_id')->comment('à¸£à¸«à¸±à¸ª staff à¸—à¸µà¹ˆà¸–à¸¹à¸à¸¡à¸­à¸šà¸«à¸¡à¸²à¸¢ (Composite PK, FK â†’ cts_staff.staff_id)');
            $table->uuid('concert_id')->comment('à¸£à¸«à¸±à¸ªà¸„à¸­à¸™à¹€à¸ªà¸´à¸£à¹Œà¸• (Composite PK, FK â†’ cts_concerts.concert_id)');
            $table->primary(['staff_id', 'concert_id']);
            $table->timestamps();

            $table->foreign('staff_id')->references('staff_id')->on('cts_staff')->onDelete('cascade');
            $table->foreign('concert_id')->references('concert_id')->on('cts_concerts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cts_staff_concerts');
    }
};
