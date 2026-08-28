<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateCtsSystemAnomaliesTable
 * create the cts_system_anomalies table for suspicious activity alerts
 * Author : Squad F (Admin/Infra)
 * Created : 28-08-2569
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cts_system_anomalies', function (Blueprint $table) {
            $table->comment('à¸šà¸±à¸™à¸—à¸¶à¸ anomaly / à¸à¸´à¸ˆà¸à¸£à¸£à¸¡à¸œà¸´à¸”à¸›à¸à¸•à¸´à¸—à¸µà¹ˆ AnomalyDetectionService à¸•à¸£à¸§à¸ˆà¸žà¸š');
            $table->uuid('anomaly_id')->primary()->comment('à¸£à¸«à¸±à¸ªà¸›à¸£à¸°à¸ˆà¸³ anomaly (UUID, PK)');
            $table->uuid('booking_id')->nullable()->comment('à¸£à¸«à¸±à¸ªà¸à¸²à¸£à¸ˆà¸­à¸‡à¸—à¸µà¹ˆà¹€à¸à¸µà¹ˆà¸¢à¸§à¸‚à¹‰à¸­à¸‡ (FK â†’ cts_bookings.booking_id, nullable)');
            $table->uuid('reviewed_by_admin_id')->nullable()->comment('à¸£à¸«à¸±à¸ª admin à¸—à¸µà¹ˆ review (FK â†’ cts_admins.admin_id, null=à¸¢à¸±à¸‡à¹„à¸¡à¹ˆ review)');
            $table->string('type', 50)->comment('à¸›à¸£à¸°à¹€à¸ à¸— anomaly à¹€à¸Šà¹ˆà¸™ double_booking, rate_limit_exceeded, suspicious_payment');
            $table->text('message')->comment('à¸£à¸²à¸¢à¸¥à¸°à¹€à¸­à¸µà¸¢à¸” anomaly à¸ªà¸³à¸«à¸£à¸±à¸š admin');
            $table->dateTime('timestamp')->comment('à¸§à¸±à¸™à¹€à¸§à¸¥à¸²à¸—à¸µà¹ˆà¹€à¸à¸´à¸” anomaly');
            $table->enum('status', ['open', 'reviewing', 'resolved', 'dismissed'])
                  ->default('open')
                  ->comment('à¸ªà¸–à¸²à¸™à¸°: open=à¸¢à¸±à¸‡à¹„à¸¡à¹ˆà¸”à¸³à¹€à¸™à¸´à¸™à¸à¸²à¸£, reviewing=à¸à¸³à¸¥à¸±à¸‡à¸•à¸£à¸§à¸ˆ, resolved=à¹à¸à¹‰à¹à¸¥à¹‰à¸§, dismissed=à¹„à¸¡à¹ˆà¹ƒà¸Šà¹ˆà¸›à¸±à¸à¸«à¸²');
            $table->text('resolve_reason')->nullable()->comment('à¹€à¸«à¸•à¸¸à¸œà¸¥à¸—à¸µà¹ˆ resolve/dismiss (null à¸–à¹‰à¸²à¸¢à¸±à¸‡à¹„à¸¡à¹ˆà¹à¸à¹‰à¹„à¸‚)');
            $table->timestamps();

            $table->foreign('booking_id')->references('booking_id')->on('cts_bookings')->onDelete('set null');
            $table->foreign('reviewed_by_admin_id')->references('admin_id')->on('cts_admins')->onDelete('set null');
            $table->index(['status', 'timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cts_system_anomalies');
    }
};
