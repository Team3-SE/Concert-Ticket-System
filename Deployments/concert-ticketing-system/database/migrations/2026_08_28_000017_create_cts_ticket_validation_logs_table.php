<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateCtsTicketValidationLogsTable
 * create the cts_ticket_validation_logs table for ticket scanning records
 * Author : Squad E (Ticket/Staff)
 * Created : 28-08-2569
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cts_ticket_validation_logs', function (Blueprint $table) {
            $table->comment('à¸šà¸±à¸™à¸—à¸¶à¸à¸—à¸¸à¸à¸„à¸£à¸±à¹‰à¸‡à¸—à¸µà¹ˆ staff à¸ªà¹à¸à¸™ ticket â€” à¸—à¸±à¹‰à¸‡ success à¹à¸¥à¸° failed');
            $table->uuid('validation_id')->primary()->comment('à¸£à¸«à¸±à¸ªà¸›à¸£à¸°à¸ˆà¸³ validation log (UUID, PK)');
            $table->uuid('ticket_id')->comment('à¸£à¸«à¸±à¸ª ticket à¸—à¸µà¹ˆà¸–à¸¹à¸à¸ªà¹à¸à¸™ (FK â†’ cts_tickets.ticket_id)');
            $table->uuid('staff_id')->comment('à¸£à¸«à¸±à¸ª staff à¸—à¸µà¹ˆà¸—à¸³à¸à¸²à¸£à¸ªà¹à¸à¸™ (FK â†’ cts_staff.staff_id)');
            $table->dateTime('validation_time')->comment('à¸§à¸±à¸™à¹€à¸§à¸¥à¸²à¸—à¸µà¹ˆà¸—à¸³à¸à¸²à¸£à¸ªà¹à¸à¸™ ticket');
            $table->boolean('result')->comment('à¸œà¸¥à¸à¸²à¸£à¸•à¸£à¸§à¸ˆà¸ªà¸­à¸š: true=à¸œà¹ˆà¸²à¸™ (valid à¸¢à¸±à¸‡à¹„à¸¡à¹ˆà¹ƒà¸Šà¹‰), false=à¹„à¸¡à¹ˆà¸œà¹ˆà¸²à¸™ (à¹ƒà¸Šà¹‰à¹à¸¥à¹‰à¸§/à¹„à¸¡à¹ˆà¸•à¸£à¸‡)');
            $table->timestamps();

            $table->foreign('ticket_id')->references('ticket_id')->on('cts_tickets')->onDelete('cascade');
            $table->foreign('staff_id')->references('staff_id')->on('cts_staff')->onDelete('restrict');
            $table->index(['ticket_id']);
            $table->index(['staff_id', 'validation_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cts_ticket_validation_logs');
    }
};
