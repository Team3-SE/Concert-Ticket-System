<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ตารางของ schema 3.1.0 รวมตารางที่เพิ่มด้วย migration ภายหลัง (booking_standing_item)
     */
    private const OLD_TABLES = [
        'ticket_validation_log',
        'ticket',
        'staff_concert',
        'seat_lock_log',
        'user_order',
        'payment',
        'booking_standing_item',
        'booking_seat',
        'seat',
        'zone_seat_pattern',
        'naming_template',
        'zone',
        'audit_log',
        'anomaly_attachment',
        'system_anomaly',
        'booking',
        'concert',
        'app_user',
        'organization',
    ];

    /**
     * ตารางของ schema 3.4.2
     */
    private const NEW_TABLES = [
        'ticket_validation_log',
        'ticket',
        'staff_concert',
        'seat_lock_log',
        'user_order',
        'payment',
        'booking_seat',
        'seat',
        'zone_inventory',
        'audit_log',
        'anomaly_attachment',
        'system_anomaly',
        'booking',
        'zone',
        'concert',
        'app_user',
        'organization',
    ];

    /**
     * up()
     * ลบตารางของ 3.1.0 แล้วสร้างตารางตาม 3.4.2
     * param : -
     * return : void
     */
    public function up(): void
    {
        $this->dropTables(self::OLD_TABLES);

        $sql = file_get_contents(base_path('concert_ticketing_schema_v3.4.2.sql'));

        // ตัดคำสั่งสร้าง/เลือก schema ออก ให้สร้างตารางลงฐานข้อมูลที่ตั้งไว้ใน .env
        $sql = preg_replace('/CREATE SCHEMA IF NOT EXISTS `concert_ticketing`[^;]*;/', '', $sql);
        $sql = preg_replace('/USE `concert_ticketing`\s*;/', '', $sql);
        $sql = str_replace('`concert_ticketing`.', '', $sql);

        DB::unprepared($sql);
    }

    /**
     * down()
     * ลบตารางของ 3.4.2 แล้วสร้างโครงสร้าง 3.1.0 กลับด้วย migration เดิม (ได้คืนแค่โครงสร้าง ข้อมูลไม่กลับมา)
     * param : -
     * return : void
     */
    public function down(): void
    {
        $this->dropTables(self::NEW_TABLES);

        foreach ([
            '2026_08_28_000001_create_concert_ticketing_schema.php',
            '2026_09_17_000001_add_banner_path_to_concert_table.php',
            '2026_10_21_000001_create_booking_standing_item_table.php',
            '2026_10_24_000001_add_remember_token_to_app_user_table.php',
        ] as $file) {
            (require database_path('migrations/' . $file))->up();
        }
    }

    /**
     * dropTables(tables)
     * ลบตารางตามรายชื่อ โดยปิดการตรวจ foreign key ระหว่างลบ
     * param : tables (array ชื่อตาราง)
     * return : void
     */
    private function dropTables(array $tables): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }
};
