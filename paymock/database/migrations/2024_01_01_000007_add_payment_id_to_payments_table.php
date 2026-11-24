<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_id')->nullable()->after('public_id');
            $table->index('payment_id');
        });

        foreach (DB::table('payments')->whereNull('payment_id')->get() as $row) {
            DB::table('payments')->where('id', $row->id)->update([
                'payment_id' => $row->order_ref,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payment_id']);
            $table->dropColumn('payment_id');
        });
    }
};
