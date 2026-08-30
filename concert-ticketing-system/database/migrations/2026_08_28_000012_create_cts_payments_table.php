<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateCtsPaymentsTable
 * create the cts_payments table for payment records (1-to-1 with booking)
 * Author : Squad D (Payment)
 * Created : 28-08-2569
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cts_payments', function (Blueprint $table) {
            $table->comment('à¸‚à¹‰à¸­à¸¡à¸¹à¸¥à¸à¸²à¸£à¸Šà¸³à¸£à¸°à¹€à¸‡à¸´à¸™ â€” 1 booking à¸•à¹ˆà¸­ 1 payment record');
            $table->uuid('payment_id')->primary()->comment('à¸£à¸«à¸±à¸ªà¸›à¸£à¸°à¸ˆà¸³à¸à¸²à¸£à¸Šà¸³à¸£à¸°à¹€à¸‡à¸´à¸™ (UUID, PK)');
            $table->uuid('booking_id')->unique()->comment('à¸£à¸«à¸±à¸ªà¸à¸²à¸£à¸ˆà¸­à¸‡ (FK â†’ cts_bookings.booking_id, unique=1-to-1)');
            $table->decimal('amount', 10, 2)->comment('à¸ˆà¸³à¸™à¸§à¸™à¹€à¸‡à¸´à¸™à¸—à¸µà¹ˆà¸Šà¸³à¸£à¸° (à¸šà¸²à¸—)');
            $table->string('method', 50)->comment('à¸§à¸´à¸˜à¸µà¸Šà¸³à¸£à¸°à¹€à¸‡à¸´à¸™ à¹€à¸Šà¹ˆà¸™ credit_card, promptpay, bank_transfer');
            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])
                  ->default('pending')
                  ->comment('à¸ªà¸–à¸²à¸™à¸°: pending=à¸£à¸­, success=à¸ªà¸³à¹€à¸£à¹‡à¸ˆ, failed=à¸¥à¹‰à¸¡à¹€à¸«à¸¥à¸§, refunded=à¸„à¸·à¸™à¹€à¸‡à¸´à¸™');
            $table->dateTime('payment_date')->nullable()->comment('à¸§à¸±à¸™à¹€à¸§à¸¥à¸²à¸—à¸µà¹ˆà¸Šà¸³à¸£à¸°à¸ªà¸³à¹€à¸£à¹‡à¸ˆ (null à¸–à¹‰à¸²à¸¢à¸±à¸‡à¹„à¸¡à¹ˆà¸ªà¸³à¹€à¸£à¹‡à¸ˆ)');
            $table->string('provider_transaction_id')->nullable()->comment('à¸£à¸«à¸±à¸ª transaction à¸ˆà¸²à¸ payment gateway à¹€à¸žà¸·à¹ˆà¸­à¸•à¸£à¸§à¸ˆà¸ªà¸­à¸šà¸¢à¹‰à¸­à¸™à¸«à¸¥à¸±à¸‡');
            $table->string('decline_code', 50)->nullable()->comment('à¸£à¸«à¸±à¸ªà¸›à¸à¸´à¹€à¸ªà¸˜à¸ˆà¸²à¸ gateway (null à¸–à¹‰à¸²à¸ªà¸³à¹€à¸£à¹‡à¸ˆ)');
            $table->string('risk_level', 20)->nullable()->comment('à¸£à¸°à¸”à¸±à¸šà¸„à¸§à¸²à¸¡à¹€à¸ªà¸µà¹ˆà¸¢à¸‡à¸ˆà¸²à¸ gateway à¹€à¸Šà¹ˆà¸™ low, medium, high');
            $table->string('card_fingerprint')->nullable()->comment('fingerprint à¸‚à¸­à¸‡à¸šà¸±à¸•à¸£ à¹ƒà¸Šà¹‰à¸•à¸£à¸§à¸ˆà¸ˆà¸±à¸š fraud/duplicate');
            $table->timestamps();

            $table->foreign('booking_id')->references('booking_id')->on('cts_bookings')->onDelete('restrict');
            $table->index(['status', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cts_payments');
    }
};
