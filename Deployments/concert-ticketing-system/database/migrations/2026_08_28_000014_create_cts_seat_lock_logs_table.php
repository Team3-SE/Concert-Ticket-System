<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateCtsSeatLockLogsTable
 * create the cts_seat_lock_logs table for Redis seat lock history
 * Author : Squad C (Booking/Lock)
 * Created : 28-08-2569
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cts_seat_lock_logs', function (Blueprint $table) {
            $table->comment('à¸›à¸£à¸°à¸§à¸±à¸•à¸´à¸à¸²à¸£à¸¥à¹‡à¸­à¸à¸—à¸µà¹ˆà¸™à¸±à¹ˆà¸‡à¸Šà¸±à¹ˆà¸§à¸„à¸£à¸²à¸§à¸œà¹ˆà¸²à¸™ Redis (SET seat:{id} {userId} NX EX 600)');
            $table->uuid('lock_id')->primary()->comment('à¸£à¸«à¸±à¸ªà¸›à¸£à¸°à¸ˆà¸³ lock log (UUID, PK)');
            $table->uuid('booking_id')->nullable()->comment('à¸£à¸«à¸±à¸ªà¸à¸²à¸£à¸ˆà¸­à¸‡à¸—à¸µà¹ˆà¹€à¸à¸µà¹ˆà¸¢à¸§à¸‚à¹‰à¸­à¸‡ (FK â†’ cts_bookings.booking_id, nullable à¹€à¸žà¸£à¸²à¸° lock à¸ªà¸£à¹‰à¸²à¸‡à¸à¹ˆà¸­à¸™ booking)');
            $table->uuid('seat_id')->comment('à¸£à¸«à¸±à¸ªà¸—à¸µà¹ˆà¸™à¸±à¹ˆà¸‡à¸—à¸µà¹ˆà¸–à¸¹à¸à¸¥à¹‡à¸­à¸ (FK â†’ cts_seats.seat_id)');
            $table->dateTime('locked_at')->comment('à¸§à¸±à¸™à¹€à¸§à¸¥à¸²à¸—à¸µà¹ˆà¹€à¸£à¸´à¹ˆà¸¡à¸¥à¹‡à¸­à¸à¸—à¸µà¹ˆà¸™à¸±à¹ˆà¸‡');
            $table->dateTime('expired_at')->comment('à¸§à¸±à¸™à¹€à¸§à¸¥à¸²à¸—à¸µà¹ˆ lock à¸«à¸¡à¸”à¸­à¸²à¸¢à¸¸à¸­à¸±à¸•à¹‚à¸™à¸¡à¸±à¸•à¸´ (locked_at + TTL 10 à¸™à¸²à¸—à¸µ)');
            $table->dateTime('released_at')->nullable()->comment('à¸§à¸±à¸™à¹€à¸§à¸¥à¸²à¸—à¸µà¹ˆà¸›à¸¥à¹ˆà¸­à¸¢ lock à¸à¹ˆà¸­à¸™à¸«à¸¡à¸”à¸­à¸²à¸¢à¸¸ (null=à¸¢à¸±à¸‡à¹„à¸¡à¹ˆà¸›à¸¥à¹ˆà¸­à¸¢)');
            $table->enum('status', ['active', 'expired', 'released'])
                  ->default('active')
                  ->comment('à¸ªà¸–à¸²à¸™à¸° lock: active=à¸à¸³à¸¥à¸±à¸‡à¸¥à¹‡à¸­à¸, expired=à¸«à¸¡à¸”à¸­à¸²à¸¢à¸¸, released=à¸–à¸¹à¸à¸›à¸¥à¹ˆà¸­à¸¢à¹à¸¥à¹‰à¸§');
            $table->timestamps();

            $table->foreign('booking_id')->references('booking_id')->on('cts_bookings')->onDelete('set null');
            $table->foreign('seat_id')->references('seat_id')->on('cts_seats')->onDelete('cascade');
            $table->index(['seat_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cts_seat_lock_logs');
    }
};
