<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company', function (Blueprint $table) {
            $table->uuid('company_id')->primary();
            $table->string('name');
            $table->string('initials');
            $table->text('description')->nullable();
            $table->dateTime('created_at');
        });

        Schema::create('app_user', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->string('password_hash');
            $table->string('role');
            $table->string('account_status');
            $table->dateTime('created_at');
        });

        Schema::create('customer', function (Blueprint $table) {
            $table->uuid('customer_id')->primary();
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('first_name');
            $table->string('last_name');

            $table->foreign('customer_id')->references('user_id')->on('app_user')->onDelete('cascade');
        });

        Schema::create('event_organizer', function (Blueprint $table) {
            $table->uuid('organizer_id')->primary();
            $table->uuid('company_id');
            $table->string('email')->unique();
            $table->string('username')->unique();

            $table->foreign('organizer_id')->references('user_id')->on('app_user')->onDelete('cascade');
            $table->foreign('company_id')->references('company_id')->on('company')->onDelete('cascade');
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->uuid('staff_id')->primary();
            $table->uuid('company_id');
            $table->uuid('created_by_organizer_id')->nullable();
            $table->string('username')->unique();

            $table->foreign('staff_id')->references('user_id')->on('app_user')->onDelete('cascade');
            $table->foreign('company_id')->references('company_id')->on('company')->onDelete('cascade');
            $table->foreign('created_by_organizer_id')->references('organizer_id')->on('event_organizer')->onDelete('set null');
        });

        Schema::create('admin', function (Blueprint $table) {
            $table->uuid('admin_id')->primary();
            $table->string('email')->unique();
            $table->string('username')->unique();

            $table->foreign('admin_id')->references('user_id')->on('app_user')->onDelete('cascade');
        });

        Schema::create('concert', function (Blueprint $table) {
            $table->uuid('concert_id')->primary();
            $table->uuid('company_id');
            $table->uuid('organizer_id');
            $table->string('name');
            $table->string('artist');
            $table->string('venue');
            $table->dateTime('event_datetime');
            $table->text('description')->nullable();
            $table->string('poster_path')->nullable();
            $table->string('seat_map_image_path')->nullable();
            $table->dateTime('sale_start_datetime');
            $table->dateTime('sale_end_datetime');
            $table->integer('max_ticket_per_order');
            $table->string('status');

            $table->foreign('company_id')->references('company_id')->on('company')->onDelete('restrict');
            $table->foreign('organizer_id')->references('organizer_id')->on('event_organizer')->onDelete('restrict');
        });

        Schema::create('concert_contact', function (Blueprint $table) {
            $table->uuid('contact_id')->primary();
            $table->uuid('concert_id');
            $table->string('contact_type');
            $table->string('value');

            $table->foreign('concert_id')->references('concert_id')->on('concert')->onDelete('cascade');
        });

        Schema::create('zone', function (Blueprint $table) {
            $table->uuid('zone_id')->primary();
            $table->uuid('concert_id');
            $table->string('name');
            $table->string('zone_type');
            $table->decimal('price', 10, 2);
            $table->integer('capacity');
            $table->string('color');

            $table->foreign('concert_id')->references('concert_id')->on('concert')->onDelete('cascade');
        });

        Schema::create('zone_seat_pattern', function (Blueprint $table) {
            $table->uuid('pattern_id')->primary();
            $table->uuid('zone_id');
            $table->string('pattern_type');
            $table->string('start_row');
            $table->integer('start_seat_number');
            $table->integer('row_count');
            $table->integer('seats_per_row');

            $table->foreign('zone_id')->references('zone_id')->on('zone')->onDelete('cascade');
        });

        Schema::create('seat', function (Blueprint $table) {
            $table->uuid('seat_id')->primary();
            $table->uuid('zone_id');
            $table->string('row');
            $table->integer('number');
            $table->string('status');

            $table->foreign('zone_id')->references('zone_id')->on('zone')->onDelete('cascade');
            $table->unique(['zone_id', 'row', 'number']);
        });

        Schema::create('booking', function (Blueprint $table) {
            $table->uuid('booking_id')->primary();
            $table->uuid('customer_id');
            $table->uuid('concert_id');
            $table->dateTime('booking_date');
            $table->decimal('total_amount', 10, 2);
            $table->string('status');
            $table->dateTime('expiry_time');

            $table->foreign('customer_id')->references('customer_id')->on('customer')->onDelete('restrict');
            $table->foreign('concert_id')->references('concert_id')->on('concert')->onDelete('restrict');
        });

        Schema::create('booking_seat', function (Blueprint $table) {
            $table->uuid('booking_id');
            $table->uuid('seat_id');
            $table->primary(['booking_id', 'seat_id']);

            $table->foreign('booking_id')->references('booking_id')->on('booking')->onDelete('cascade');
            $table->foreign('seat_id')->references('seat_id')->on('seat')->onDelete('restrict');
        });

        Schema::create('booking_standing_item', function (Blueprint $table) {
            $table->uuid('booking_id');
            $table->uuid('zone_id');
            $table->integer('quantity');
            $table->primary(['booking_id', 'zone_id']);

            $table->foreign('booking_id')->references('booking_id')->on('booking')->onDelete('cascade');
            $table->foreign('zone_id')->references('zone_id')->on('zone')->onDelete('restrict');
        });

        Schema::create('payment', function (Blueprint $table) {
            $table->uuid('payment_id')->primary();
            $table->uuid('booking_id')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('method');
            $table->string('status');
            $table->dateTime('payment_date')->nullable();
            $table->string('provider_transaction_id')->nullable()->unique();
            $table->string('decline_code')->nullable();
            $table->string('risk_level')->nullable();
            $table->string('card_fingerprint')->nullable();

            $table->foreign('booking_id')->references('booking_id')->on('booking')->onDelete('restrict');
        });

        Schema::create('chargeback', function (Blueprint $table) {
            $table->uuid('chargeback_id')->primary();
            $table->uuid('payment_id');
            $table->string('provider_chargeback_id')->unique();
            $table->string('reason');
            $table->decimal('amount', 10, 2);
            $table->dateTime('created_at');
            $table->dateTime('resolved_at')->nullable();

            $table->foreign('payment_id')->references('payment_id')->on('payment')->onDelete('restrict');
        });

        Schema::create('customer_order', function (Blueprint $table) {
            $table->uuid('order_id')->primary();
            $table->uuid('booking_id')->unique();
            $table->uuid('payment_id')->unique();
            $table->dateTime('order_date');

            $table->foreign('booking_id')->references('booking_id')->on('booking')->onDelete('restrict');
            $table->foreign('payment_id')->references('payment_id')->on('payment')->onDelete('restrict');
        });

        Schema::create('ticket', function (Blueprint $table) {
            $table->uuid('ticket_id')->primary();
            $table->uuid('order_id');
            $table->uuid('concert_id');
            $table->uuid('zone_id');
            $table->uuid('seat_id')->nullable()->unique();
            $table->string('qr_code')->unique();
            $table->boolean('is_used');

            $table->foreign('order_id')->references('order_id')->on('customer_order')->onDelete('restrict');
            $table->foreign('concert_id')->references('concert_id')->on('concert')->onDelete('restrict');
            $table->foreign('zone_id')->references('zone_id')->on('zone')->onDelete('restrict');
            $table->foreign('seat_id')->references('seat_id')->on('seat')->onDelete('restrict');
        });

        Schema::create('staff_concert', function (Blueprint $table) {
            $table->uuid('staff_id');
            $table->uuid('concert_id');
            $table->primary(['staff_id', 'concert_id']);

            $table->foreign('staff_id')->references('staff_id')->on('staff')->onDelete('cascade');
            $table->foreign('concert_id')->references('concert_id')->on('concert')->onDelete('cascade');
        });

        Schema::create('ticket_validation_log', function (Blueprint $table) {
            $table->uuid('validation_id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('staff_id');
            $table->dateTime('validation_time');
            $table->boolean('result');

            $table->foreign('ticket_id')->references('ticket_id')->on('ticket')->onDelete('cascade');
            $table->foreign('staff_id')->references('staff_id')->on('staff')->onDelete('restrict');
        });

        Schema::create('seat_lock_log', function (Blueprint $table) {
            $table->uuid('lock_id')->primary();
            $table->uuid('booking_id')->nullable();
            $table->uuid('seat_id');
            $table->dateTime('locked_at');
            $table->dateTime('expired_at');
            $table->dateTime('released_at')->nullable();
            $table->string('status');

            $table->foreign('booking_id')->references('booking_id')->on('booking')->onDelete('set null');
            $table->foreign('seat_id')->references('seat_id')->on('seat')->onDelete('cascade');
        });

        Schema::create('system_anomaly', function (Blueprint $table) {
            $table->uuid('anomaly_id')->primary();
            $table->uuid('booking_id')->nullable();
            $table->uuid('reviewed_by_admin_id')->nullable();
            $table->string('type');
            $table->text('message');
            $table->dateTime('timestamp');
            $table->string('status');
            $table->text('resolve_reason')->nullable();

            $table->foreign('booking_id')->references('booking_id')->on('booking')->onDelete('set null');
            $table->foreign('reviewed_by_admin_id')->references('admin_id')->on('admin')->onDelete('set null');
        });

        Schema::create('anomaly_attachment', function (Blueprint $table) {
            $table->uuid('attachment_id')->primary();
            $table->uuid('anomaly_id');
            $table->string('file_path');

            $table->foreign('anomaly_id')->references('anomaly_id')->on('system_anomaly')->onDelete('cascade');
        });

        Schema::create('audit_log', function (Blueprint $table) {
            $table->uuid('log_id')->primary();
            $table->uuid('user_id')->nullable();
            $table->string('action');
            $table->dateTime('timestamp');
            $table->string('ip_address')->nullable();

            $table->foreign('user_id')->references('user_id')->on('app_user')->onDelete('set null');
        });
    }

    public function down(): void
    {
        foreach ([
            'audit_log',
            'anomaly_attachment',
            'system_anomaly',
            'seat_lock_log',
            'ticket_validation_log',
            'staff_concert',
            'ticket',
            'customer_order',
            'chargeback',
            'payment',
            'booking_standing_item',
            'booking_seat',
            'booking',
            'seat',
            'zone_seat_pattern',
            'zone',
            'concert_contact',
            'concert',
            'admin',
            'staff',
            'event_organizer',
            'customer',
            'app_user',
            'company',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
