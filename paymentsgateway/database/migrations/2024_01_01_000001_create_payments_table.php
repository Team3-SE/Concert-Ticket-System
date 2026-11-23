<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();          // txn_xxx
            $table->string('order_ref');                     // booking_code from concert system
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('THB');
            $table->string('method', 50);                   // card | promptpay
            $table->string('status', 50)->default('PENDING'); // PENDING|COMPLETED|FAILED|EXPIRED
            $table->string('scenario')->nullable();          // chaos/webhook scenario
            $table->string('failure_code')->nullable();
            $table->text('callback_url');                    // webhook endpoint of concert system
            $table->text('return_url')->nullable();          // redirect after payment
            $table->string('idempotency_key')->unique();     // prevents duplicate creation
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('order_ref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
