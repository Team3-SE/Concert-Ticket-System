<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['order_ref']);
            $table->dropIndex(['payment_id']);
            $table->dropColumn(['order_ref', 'payment_id']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('order_ref')->nullable();
            $table->string('payment_id')->nullable();
            $table->index('order_ref');
            $table->index('payment_id');
        });
    }
};
