<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateCtsAuditLogsTable
 * create the cts_audit_logs table for tracking all important user actions
 * Author : Squad F (Admin/Infra)
 * Created : 28-08-2569
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cts_audit_logs', function (Blueprint $table) {
            $table->comment('à¸šà¸±à¸™à¸—à¸¶à¸à¸à¸²à¸£à¸à¸£à¸°à¸—à¸³à¸ªà¸³à¸„à¸±à¸à¸—à¸¸à¸à¸­à¸¢à¹ˆà¸²à¸‡à¹ƒà¸™à¸£à¸°à¸šà¸š (Actor, Action, Target, Timestamp, IP)');
            $table->uuid('log_id')->primary()->comment('à¸£à¸«à¸±à¸ªà¸›à¸£à¸°à¸ˆà¸³ audit log (UUID, PK)');
            $table->uuid('user_id')->nullable()->comment('à¸£à¸«à¸±à¸ªà¸œà¸¹à¹‰à¹ƒà¸Šà¹‰à¸—à¸µà¹ˆà¸—à¸³ action (FK â†’ cts_users.user_id, nullable à¸à¸£à¸“à¸µ system à¸«à¸£à¸·à¸­ user à¸–à¸¹à¸à¸¥à¸š)');
            $table->string('action', 100)->comment('à¸›à¸£à¸°à¹€à¸ à¸— action à¹€à¸Šà¹ˆà¸™ login_success, booking_create, payment_attempt, ticket_validate');
            $table->dateTime('timestamp')->comment('à¸§à¸±à¸™à¹€à¸§à¸¥à¸²à¸—à¸µà¹ˆà¹€à¸à¸´à¸” action');
            $table->string('ip_address', 45)->nullable()->comment('IP address à¸‚à¸­à¸‡à¸œà¸¹à¹‰à¹ƒà¸Šà¹‰ (à¸£à¸­à¸‡à¸£à¸±à¸š IPv4 à¹à¸¥à¸° IPv6 max 45 chars)');
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('cts_users')->onDelete('set null');
            $table->index(['user_id', 'action']);
            $table->index(['timestamp']);
            $table->index(['action', 'timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cts_audit_logs');
    }
};
