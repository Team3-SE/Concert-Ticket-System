<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * up()
     * สร้างตาราง booking_standing_item (1 แถว = 1 โซนยืนใน 1 การจอง)
     * param : -
     * return : void
     */
    public function up(): void
    {
        Schema::create('booking_standing_item', function (Blueprint $table) {
            $table->integer('booking_id')->comment('รหัสประจำการจอง (ตาราง booking)');
            $table->integer('zone_id')->comment('รหัสประจำโซนยืน (ตาราง zone)');
            $table->integer('quantity')->comment('จำนวนบัตรโซนยืนที่จอง');
            $table->string('zone_name_snapshot')->nullable()->comment('ชื่อโซน ณ เวลาที่จอง');
            $table->decimal('unit_price_snapshot', 12, 2)->comment('ราคาต่อบัตร ณ เวลาที่จอง');

            $table->primary(['booking_id', 'zone_id']);
            $table->foreign('booking_id', 'fk_standing_item_booking')->references('booking_id')->on('booking');
            $table->foreign('zone_id', 'fk_standing_item_zone')->references('zone_id')->on('zone');
        });
    }

    /**
     * down()
     * ลบตาราง booking_standing_item
     * param : -
     * return : void
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_standing_item');
    }
};
