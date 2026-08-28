<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateCtsAnomalyAttachmentsTable
 * create the cts_anomaly_attachments table for files attached to anomaly reports
 * Author : Squad F (Admin/Infra)
 * Created : 28-08-2569
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cts_anomaly_attachments', function (Blueprint $table) {
            $table->comment('à¹„à¸Ÿà¸¥à¹Œà¹à¸™à¸šà¸—à¸µà¹ˆà¹€à¸à¸µà¹ˆà¸¢à¸§à¸‚à¹‰à¸­à¸‡à¸à¸±à¸š anomaly à¹€à¸Šà¹ˆà¸™ log dump, screenshot');
            $table->uuid('attachment_id')->primary()->comment('à¸£à¸«à¸±à¸ªà¸›à¸£à¸°à¸ˆà¸³ attachment (UUID, PK)');
            $table->uuid('anomaly_id')->comment('à¸£à¸«à¸±à¸ª anomaly à¸—à¸µà¹ˆà¹„à¸Ÿà¸¥à¹Œà¸™à¸µà¹‰à¹à¸™à¸š (FK â†’ cts_system_anomalies.anomaly_id)');
            $table->string('file_path')->comment('path à¸‚à¸­à¸‡à¹„à¸Ÿà¸¥à¹Œà¹ƒà¸™ storage à¹€à¸Šà¹ˆà¸™ storage/anomaly-attachments/...');
            $table->timestamps();

            $table->foreign('anomaly_id')->references('anomaly_id')->on('cts_system_anomalies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cts_anomaly_attachments');
    }
};
