<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateCtsChargebacksTable
 * create the cts_chargebacks table for payment dispute records
 * Author : Squad D (Payment)
 * Created : 28-08-2569
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cts_chargebacks', function (Blueprint $table) {
            $table->comment('à¸šà¸±à¸™à¸—à¸¶à¸ chargeback / à¸à¸²à¸£à¸„à¸±à¸”à¸„à¹‰à¸²à¸™à¸à¸²à¸£à¸Šà¸³à¸£à¸°à¹€à¸‡à¸´à¸™à¸—à¸µà¹ˆà¹„à¸”à¹‰à¸£à¸±à¸šà¸ˆà¸²à¸ payment gateway');
            $table->uuid('chargeback_id')->primary()->comment('à¸£à¸«à¸±à¸ªà¸›à¸£à¸°à¸ˆà¸³ chargeback (UUID, PK)');
            $table->uuid('payment_id')->comment('à¸£à¸«à¸±à¸ªà¸à¸²à¸£à¸Šà¸³à¸£à¸°à¹€à¸‡à¸´à¸™à¸—à¸µà¹ˆà¸–à¸¹à¸ chargeback (FK â†’ cts_payments.payment_id)');
            $table->string('provider_chargeback_id')->unique()->comment('à¸£à¸«à¸±à¸ª chargeback à¸ˆà¸²à¸ payment gateway (unique)');
            $table->string('reason')->comment('à¹€à¸«à¸•à¸¸à¸œà¸¥à¸‚à¸­à¸‡ chargeback à¸•à¸²à¸¡ code à¸ˆà¸²à¸ gateway');
            $table->decimal('amount', 10, 2)->comment('à¸ˆà¸³à¸™à¸§à¸™à¹€à¸‡à¸´à¸™à¸—à¸µà¹ˆà¸–à¸¹à¸ chargeback (à¸šà¸²à¸—)');
            $table->enum('status', ['open', 'won', 'lost', 'withdrawn'])
                  ->default('open')
                  ->comment('à¸ªà¸–à¸²à¸™à¸°: open=à¸£à¸­à¸”à¸³à¹€à¸™à¸´à¸™à¸à¸²à¸£, won=à¸Šà¸™à¸°, lost=à¹à¸žà¹‰, withdrawn=à¸–à¸­à¸™à¸„à¸”à¸µ');
            $table->dateTime('created_at')->comment('à¸§à¸±à¸™à¹€à¸§à¸¥à¸²à¸—à¸µà¹ˆà¹„à¸”à¹‰à¸£à¸±à¸š chargeback à¸ˆà¸²à¸ gateway');
            $table->dateTime('resolved_at')->nullable()->comment('à¸§à¸±à¸™à¹€à¸§à¸¥à¸²à¸—à¸µà¹ˆ chargeback à¸–à¸¹à¸à¸•à¸±à¸”à¸ªà¸´à¸™ (null=à¸¢à¸±à¸‡à¸£à¸­)');
            $table->timestamp('updated_at')->nullable()->comment('à¸§à¸±à¸™à¹€à¸§à¸¥à¸²à¸—à¸µà¹ˆà¸‚à¹‰à¸­à¸¡à¸¹à¸¥à¸–à¸¹à¸à¹à¸à¹‰à¹„à¸‚à¸¥à¹ˆà¸²à¸ªà¸¸à¸”');

            $table->foreign('payment_id')->references('payment_id')->on('cts_payments')->onDelete('restrict');
            $table->index(['payment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cts_chargebacks');
    }
};
