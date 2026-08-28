<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateCtsZonesTable
 * create the cts_zones table for seating zones within a concert
 * Author : Squad B (Concert)
 * Created : 28-08-2569
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cts_zones', function (Blueprint $table) {
            $table->comment('à¹‚à¸‹à¸™à¸—à¸µà¹ˆà¸™à¸±à¹ˆà¸‡à¸ à¸²à¸¢à¹ƒà¸™à¸„à¸­à¸™à¹€à¸ªà¸´à¸£à¹Œà¸• à¹€à¸Šà¹ˆà¸™ VIP, Zone A, Zone B');
            $table->uuid('zone_id')->primary()->comment('à¸£à¸«à¸±à¸ªà¸›à¸£à¸°à¸ˆà¸³à¹‚à¸‹à¸™ (UUID, PK)');
            $table->uuid('concert_id')->comment('à¸£à¸«à¸±à¸ªà¸„à¸­à¸™à¹€à¸ªà¸´à¸£à¹Œà¸•à¸—à¸µà¹ˆà¹‚à¸‹à¸™à¸™à¸µà¹‰à¸ªà¸±à¸‡à¸à¸±à¸” (FK â†’ cts_concerts.concert_id)');
            $table->string('name', 50)->comment('à¸Šà¸·à¹ˆà¸­à¹‚à¸‹à¸™ à¹€à¸Šà¹ˆà¸™ VIP, Zone A, Premium');
            $table->decimal('price', 10, 2)->comment('à¸£à¸²à¸„à¸²à¸•à¹ˆà¸­à¸—à¸µà¹ˆà¸™à¸±à¹ˆà¸‡à¹ƒà¸™à¹‚à¸‹à¸™à¸™à¸µà¹‰ (à¸šà¸²à¸—)');
            $table->integer('capacity')->comment('à¸ˆà¸³à¸™à¸§à¸™à¸—à¸µà¹ˆà¸™à¸±à¹ˆà¸‡à¸—à¸±à¹‰à¸‡à¸«à¸¡à¸”à¹ƒà¸™à¹‚à¸‹à¸™');
            $table->timestamps();

            $table->foreign('concert_id')->references('concert_id')->on('cts_concerts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cts_zones');
    }
};
