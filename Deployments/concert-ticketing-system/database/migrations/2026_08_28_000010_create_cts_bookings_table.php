<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateCtsBookingsTable
 * create the cts_bookings table for concert seat booking transactions
 * Author : Squad C (Booking)
 * Created : 28-08-2569
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cts_bookings', function (Blueprint $table) {
            $table->comment('à¸à¸²à¸£à¸ˆà¸­à¸‡à¸•à¸±à¹‹à¸§à¸„à¸­à¸™à¹€à¸ªà¸´à¸£à¹Œà¸• â€” à¹à¸•à¹ˆà¸¥à¸°à¹à¸–à¸§à¹à¸—à¸™ 1 à¸˜à¸¸à¸£à¸à¸£à¸£à¸¡à¸à¸²à¸£à¸ˆà¸­à¸‡à¸‚à¸­à¸‡à¸¥à¸¹à¸à¸„à¹‰à¸²');
            $table->uuid('booking_id')->primary()->comment('à¸£à¸«à¸±à¸ªà¸›à¸£à¸°à¸ˆà¸³à¸à¸²à¸£à¸ˆà¸­à¸‡ (UUID, PK)');
            $table->uuid('customer_id')->comment('à¸£à¸«à¸±à¸ªà¸¥à¸¹à¸à¸„à¹‰à¸²à¸—à¸µà¹ˆà¸ˆà¸­à¸‡ (FK â†’ cts_customers.customer_id)');
            $table->uuid('concert_id')->comment('à¸£à¸«à¸±à¸ªà¸„à¸­à¸™à¹€à¸ªà¸´à¸£à¹Œà¸•à¸—à¸µà¹ˆà¸ˆà¸­à¸‡ (FK â†’ cts_concerts.concert_id)');
            $table->dateTime('booking_date')->comment('à¸§à¸±à¸™à¹€à¸§à¸¥à¸²à¸—à¸µà¹ˆà¸—à¸³à¸£à¸²à¸¢à¸à¸²à¸£à¸ˆà¸­à¸‡');
            $table->decimal('total_amount', 10, 2)->comment('à¸¢à¸­à¸”à¸£à¸§à¸¡à¸—à¸µà¹ˆà¸•à¹‰à¸­à¸‡à¸Šà¸³à¸£à¸° (à¸šà¸²à¸—)');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'expired'])
                  ->default('pending')
                  ->comment('à¸ªà¸–à¸²à¸™à¸°: pending=à¸£à¸­à¸Šà¸³à¸£à¸°, confirmed=à¸Šà¸³à¸£à¸°à¹à¸¥à¹‰à¸§, cancelled=à¸¢à¸à¹€à¸¥à¸´à¸, expired=à¸«à¸¡à¸”à¸­à¸²à¸¢à¸¸');
            $table->dateTime('expiry_time')->comment('à¸§à¸±à¸™à¹€à¸§à¸¥à¸²à¸—à¸µà¹ˆà¸à¸²à¸£à¸ˆà¸­à¸‡à¸«à¸¡à¸”à¸­à¸²à¸¢à¸¸à¸–à¹‰à¸²à¸¢à¸±à¸‡à¹„à¸¡à¹ˆà¸Šà¸³à¸£à¸° (booking_date + 10 à¸™à¸²à¸—à¸µ)');
            $table->timestamps();

            $table->foreign('customer_id')->references('customer_id')->on('cts_customers')->onDelete('restrict');
            $table->foreign('concert_id')->references('concert_id')->on('cts_concerts')->onDelete('restrict');
            $table->index(['customer_id', 'status']);
            $table->index(['concert_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cts_bookings');
    }
};
