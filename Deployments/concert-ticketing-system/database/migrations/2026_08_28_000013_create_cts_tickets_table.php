<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateCtsTicketsTable
 * create the cts_tickets table for e-tickets generated after successful payment
 * Author : Squad E (Ticket)
 * Created : 28-08-2569
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cts_tickets', function (Blueprint $table) {
            $table->comment('E-ticket à¸«à¸¥à¸±à¸‡à¸Šà¸³à¸£à¸°à¹€à¸‡à¸´à¸™à¸ªà¸³à¹€à¸£à¹‡à¸ˆ â€” 1 à¸—à¸µà¹ˆà¸™à¸±à¹ˆà¸‡ = 1 ticket à¹€à¸ªà¸¡à¸­');
            $table->uuid('ticket_id')->primary()->comment('à¸£à¸«à¸±à¸ªà¸›à¸£à¸°à¸ˆà¸³ ticket (UUID, PK)');
            $table->uuid('booking_id')->comment('à¸£à¸«à¸±à¸ªà¸à¸²à¸£à¸ˆà¸­à¸‡à¸—à¸µà¹ˆà¸ªà¸£à¹‰à¸²à¸‡ ticket à¸™à¸µà¹‰ (FK â†’ cts_bookings.booking_id)');
            $table->uuid('concert_id')->comment('à¸£à¸«à¸±à¸ªà¸„à¸­à¸™à¹€à¸ªà¸´à¸£à¹Œà¸•à¹ƒà¸™à¸•à¸±à¹‹à¸§ (FK â†’ cts_concerts.concert_id)');
            $table->uuid('seat_id')->unique()->comment('à¸£à¸«à¸±à¸ªà¸—à¸µà¹ˆà¸™à¸±à¹ˆà¸‡ (FK â†’ cts_seats.seat_id, unique=1 à¸—à¸µà¹ˆà¸™à¸±à¹ˆà¸‡ à¸•à¹ˆà¸­ 1 ticket)');
            $table->string('qr_code')->unique()->comment('secure token à¸ªà¸³à¸«à¸£à¸±à¸š QR Code â€” à¸«à¹‰à¸²à¸¡à¸à¸±à¸‡ sensitive data à¸•à¹‰à¸­à¸‡ hash à¹€à¸—à¹ˆà¸²à¸™à¸±à¹‰à¸™');
            $table->boolean('is_used')->default(false)->comment('à¸ªà¸–à¸²à¸™à¸°: false=à¸¢à¸±à¸‡à¹„à¸¡à¹ˆà¹ƒà¸Šà¹‰, true=à¹ƒà¸Šà¹‰à¹à¸¥à¹‰à¸§ (validate à¹„à¸”à¹‰à¸„à¸£à¸±à¹‰à¸‡à¹€à¸”à¸µà¸¢à¸§)');
            $table->timestamps();

            $table->foreign('booking_id')->references('booking_id')->on('cts_bookings')->onDelete('restrict');
            $table->foreign('concert_id')->references('concert_id')->on('cts_concerts')->onDelete('restrict');
            $table->foreign('seat_id')->references('seat_id')->on('cts_seats')->onDelete('restrict');
            $table->index(['booking_id']);
            $table->index(['concert_id', 'is_used']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cts_tickets');
    }
};
