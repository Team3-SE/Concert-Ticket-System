<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateCtsSeatsTable
 * create the cts_seats table for individual seats within a zone
 * Author : Squad B (Concert)
 * Created : 28-08-2569
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cts_seats', function (Blueprint $table) {
            $table->comment('à¸—à¸µà¹ˆà¸™à¸±à¹ˆà¸‡à¹à¸•à¹ˆà¸¥à¸°à¸—à¸µà¹ˆà¸ à¸²à¸¢à¹ƒà¸™à¹‚à¸‹à¸™ â€” MySQL à¹€à¸›à¹‡à¸™ source of truth à¸ªà¸–à¸²à¸™à¸°à¸–à¸²à¸§à¸£');
            $table->uuid('seat_id')->primary()->comment('à¸£à¸«à¸±à¸ªà¸›à¸£à¸°à¸ˆà¸³à¸—à¸µà¹ˆà¸™à¸±à¹ˆà¸‡ (UUID, PK)');
            $table->uuid('zone_id')->comment('à¸£à¸«à¸±à¸ªà¹‚à¸‹à¸™à¸—à¸µà¹ˆà¸—à¸µà¹ˆà¸™à¸±à¹ˆà¸‡à¸™à¸µà¹‰à¸­à¸¢à¸¹à¹ˆ (FK â†’ cts_zones.zone_id)');
            $table->string('row', 5)->comment('à¹à¸–à¸§à¸‚à¸­à¸‡à¸—à¸µà¹ˆà¸™à¸±à¹ˆà¸‡ à¹€à¸Šà¹ˆà¸™ A, B, C');
            $table->integer('number')->comment('à¸«à¸¡à¸²à¸¢à¹€à¸¥à¸‚à¸—à¸µà¹ˆà¸™à¸±à¹ˆà¸‡à¹ƒà¸™à¹à¸–à¸§ à¹€à¸£à¸´à¹ˆà¸¡à¸ˆà¸²à¸ 1');
            $table->enum('status', ['available', 'locked', 'booked'])
                  ->default('available')
                  ->comment('à¸ªà¸–à¸²à¸™à¸°: available=à¸§à¹ˆà¸²à¸‡, locked=à¸¥à¹‡à¸­à¸„ Redis à¸Šà¸±à¹ˆà¸§à¸„à¸£à¸²à¸§, booked=à¸ˆà¸­à¸‡à¸ªà¸³à¹€à¸£à¹‡à¸ˆà¹à¸¥à¹‰à¸§');
            $table->timestamps();

            $table->foreign('zone_id')->references('zone_id')->on('cts_zones')->onDelete('cascade');
            $table->unique(['zone_id', 'row', 'number']);
            $table->index(['zone_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cts_seats');
    }
};
