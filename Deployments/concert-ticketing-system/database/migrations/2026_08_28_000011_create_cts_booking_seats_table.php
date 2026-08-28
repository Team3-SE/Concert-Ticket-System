<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateCtsBookingSeatsTable
 * create the cts_booking_seats junction table linking bookings to seats
 * Author : Squad C (Booking)
 * Created : 28-08-2569
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cts_booking_seats', function (Blueprint $table) {
            $table->comment('à¸•à¸²à¸£à¸²à¸‡ junction à¹€à¸Šà¸·à¹ˆà¸­à¸¡ booking à¸à¸±à¸š seat (1 booking à¸¡à¸µà¸«à¸¥à¸²à¸¢à¸—à¸µà¹ˆà¸™à¸±à¹ˆà¸‡à¹„à¸”à¹‰)');
            $table->uuid('booking_id')->comment('à¸£à¸«à¸±à¸ªà¸à¸²à¸£à¸ˆà¸­à¸‡ (Composite PK, FK â†’ cts_bookings.booking_id)');
            $table->uuid('seat_id')->comment('à¸£à¸«à¸±à¸ªà¸—à¸µà¹ˆà¸™à¸±à¹ˆà¸‡ (Composite PK, FK â†’ cts_seats.seat_id)');
            $table->primary(['booking_id', 'seat_id']);
            $table->timestamps();

            $table->foreign('booking_id')->references('booking_id')->on('cts_bookings')->onDelete('cascade');
            $table->foreign('seat_id')->references('seat_id')->on('cts_seats')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cts_booking_seats');
    }
};
