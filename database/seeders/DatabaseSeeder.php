<?php

/**
 * DatabaseSeeder
 * ข้อมูลตัวอย่างสำหรับ schema 3.4.2 (องค์กร ผู้ใช้ทุกบทบาท คอนเสิร์ต โซน ที่นั่ง และการจองที่ชำระเงินแล้ว 1 รายการ)
 * ทุกบัญชีใช้รหัสผ่าน Password123!
 * Author :
 * Created :
 * Updated : 10/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    private array $codeSequences = [];

    /**
     * run()
     * ล้างข้อมูลเดิมแล้วสร้างข้อมูลตัวอย่างใหม่ทั้งหมด
     * param : -
     * return : void
     */
    public function run(): void
    {
        $this->truncateSchema();

        $now = now();

        // Organizations (schema 3.4.2 เหลือแค่ชื่อ ช่องทางติดต่อย้ายไปอยู่ที่คอนเสิร์ต)
        $orgGmmId = DB::table('organization')->insertGetId([
            'name' => 'GMM Grammy',
            'created_at' => $now,
        ]);

        $orgBecId = DB::table('organization')->insertGetId([
            'name' => 'BEC-Tero Entertainment',
            'created_at' => $now,
        ]);

        // Users
        $adminId = DB::table('app_user')->insertGetId([
            'email' => 'admin@concert.local',
            'password_hash' => Hash::make('Password123!'),
            'role' => 'ADMIN',
            'account_status' => 1,
            'username' => 'admin',
            'created_at' => $now,
        ]);

        $orgGmmUserId = DB::table('app_user')->insertGetId([
            'email' => 'organizer@gmm.local',
            'password_hash' => Hash::make('Password123!'),
            'role' => 'ORGANIZER',
            'account_status' => 1,
            'username' => 'gmm-organizer',
            'u_organization_id' => $orgGmmId,
            'created_at' => $now,
        ]);

        $orgBecUserId = DB::table('app_user')->insertGetId([
            'email' => 'organizer@bec.local',
            'password_hash' => Hash::make('Password123!'),
            'role' => 'ORGANIZER',
            'account_status' => 1,
            'username' => 'bec-organizer',
            'u_organization_id' => $orgBecId,
            'created_at' => $now,
        ]);

        $staffGmm1Id = DB::table('app_user')->insertGetId([
            'password_hash' => Hash::make('Password123!'),
            'role' => 'STAFF',
            'account_status' => 1,
            'username' => 'gmm-gate-1',
            'u_organization_id' => $orgGmmId,
            'u_created_by_app_user_id' => $orgGmmUserId,
            'created_at' => $now,
        ]);

        $staffGmm2Id = DB::table('app_user')->insertGetId([
            'password_hash' => Hash::make('Password123!'),
            'role' => 'STAFF',
            'account_status' => 1,
            'username' => 'gmm-gate-2',
            'u_organization_id' => $orgGmmId,
            'u_created_by_app_user_id' => $orgGmmUserId,
            'created_at' => $now,
        ]);

        $staffBec1Id = DB::table('app_user')->insertGetId([
            'password_hash' => Hash::make('Password123!'),
            'role' => 'STAFF',
            'account_status' => 1,
            'username' => 'bec-gate-1',
            'u_organization_id' => $orgBecId,
            'u_created_by_app_user_id' => $orgBecUserId,
            'created_at' => $now,
        ]);

        $user1Id = DB::table('app_user')->insertGetId([
            'email' => 'user1@example.com',
            'password_hash' => Hash::make('Password123!'),
            'role' => 'USER',
            'account_status' => 1,
            'first_name' => 'Suda',
            'last_name' => 'Saengdao',
            'phone' => '0812345001',
            'created_at' => $now,
        ]);

        DB::table('app_user')->insert([
            'email' => 'user2@example.com',
            'password_hash' => Hash::make('Password123!'),
            'role' => 'USER',
            'account_status' => 1,
            'first_name' => 'Wichai',
            'last_name' => 'Rungrueang',
            'phone' => '0812345002',
            'created_at' => $now,
        ]);

        // Concerts (ช่องทางติดต่อของผู้จัดเก็บที่คอนเสิร์ต)
        $concertRockId = DB::table('concert')->insertGetId([
            'c_organization_id' => $orgGmmId,
            'name' => 'Rock Night 2026',
            'artist' => 'Bodyslam',
            'venue' => 'BCC Hall, Bangkok',
            'event_datetime' => '2026-11-01 18:00:00',
            'description' => 'A high-energy rock concert in Bangkok.',
            'phone' => '02-669-9000',
            'email' => 'contact@gmm-grammy.example',
            'facebook' => 'GMM Grammy Official',
            'line' => '@gmmgrammy',
            'poster_path' => 'posters/rock-night-2026.jpg',
            'seat_map_image_path' => 'seat-maps/rock-night-2026.png',
            'sale_start_datetime' => '2026-09-15 10:00:00',
            'sale_end_datetime' => '2026-10-31 23:59:59',
            'max_ticket_per_order' => 4,
            'status' => 'ON_SALE',
            'concert_code' => 'C0024',
            'created_at' => $now,
        ]);

        $concertPopId = DB::table('concert')->insertGetId([
            'c_organization_id' => $orgBecId,
            'name' => 'Pop Star Live 2026',
            'artist' => 'Bird Thongchai',
            'venue' => 'Impact Arena, Muang Thong Thani',
            'event_datetime' => '2026-12-15 19:00:00',
            'description' => 'A pop concert with reserved and standing zones.',
            'phone' => '02-612-9999',
            'email' => 'contact@bec-tero.example',
            'facebook' => 'BEC-Tero Entertainment',
            'line' => '@bectero',
            'poster_path' => 'posters/pop-star-live-2026.jpg',
            'seat_map_image_path' => 'seat-maps/pop-star-live-2026.png',
            'sale_start_datetime' => '2026-10-01 10:00:00',
            'sale_end_datetime' => '2026-12-14 23:59:59',
            'max_ticket_per_order' => 6,
            'status' => 'SCHEDULED',
            'concert_code' => 'C0025',
            'created_at' => $now,
        ]);

        $rockVipZoneId = $this->seedZoneWithSeats($concertRockId, 'VIP', 3000, 15, '#F59E0B', 'A', 1, 3, 5);
        $this->seedZoneWithSeats($concertRockId, 'Zone A', 1500, 24, '#3B82F6', 'D', 1, 4, 6);
        $this->seedStandingZone($concertPopId, 'Standing', 1200, 500, '#10B981');

        // staff_concert.status: 1 = ยังได้รับมอบหมายอยู่
        DB::table('staff_concert')->insert([
            ['sc_staff_id' => $staffGmm1Id, 'sc_concert_id' => $concertRockId, 'status' => 1],
            ['sc_staff_id' => $staffGmm2Id, 'sc_concert_id' => $concertRockId, 'status' => 1],
            ['sc_staff_id' => $staffBec1Id, 'sc_concert_id' => $concertPopId, 'status' => 1],
        ]);

        // การจองที่ชำระเงินแล้ว: user1 จองที่นั่ง VIP 2 ที่
        $vipSeats = DB::table('seat')->where('s_zone_id', $rockVipZoneId)->orderBy('row')->orderBy('number')->take(2)->get();

        $seatBookingId = DB::table('booking')->insertGetId([
            'booking_code' => $this->makeCode('BKG'),   // booking_code เป็นรหัสภายใน (ไม่ใช่ Public Code)
            'b_user_id' => $user1Id,
            'b_zone_id' => $rockVipZoneId,
            'booking_date' => $now->copy()->subMinutes(30),
            'total_amount' => 6000,
            'status' => 'CONFIRMED',
            'expiry_time' => $now->copy()->subMinutes(20),
            'zone_price_snapshot' => 3000,
            'zone_name_snapshot' => 'VIP',
        ]);

        foreach ($vipSeats as $seat) {
            DB::table('booking_seat')->insert([
                'bs_booking_id' => $seatBookingId,
                'bs_seat_id' => $seat->s_id,
                'seat_label_snapshot' => $seat->row . $seat->number,
            ]);
            DB::table('seat')->where('s_id', $seat->s_id)->update(['seat_booking_status' => 'sold']);
        }

        $this->seedPaymentOrderAndTickets($seatBookingId, $vipSeats->pluck('s_id')->all(), 6000, 'C024');

        $this->command->info('Seeded the v3.4.2 schema successfully.');
    }

    /**
     * truncateSchema()
     * ล้างข้อมูลทุกตารางของ schema 3.4.2 (ปิดการตรวจ foreign key ระหว่างล้าง)
     * param : -
     * return : void
     */
    private function truncateSchema(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $tables = [
            'ticket_validation_log',
            'ticket',
            'staff_concert',
            'seat_lock_log',
            'user_order',
            'payment',
            'booking_seat',
            'seat',
            'zone_inventory',
            'zone',
            'audit_log',
            'anomaly_attachment',
            'system_anomaly',
            'booking',
            'concert',
            'app_user',
            'organization',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * seedZoneWithSeats(concertId, name, price, capacity, color, startRow, startSeatNumber, rowCount, seatsPerRow)
     * สร้างโซนที่นั่ง (SEATED) พร้อมที่นั่งทุกที่ (แถวเริ่มจาก startRow เลขที่นั่งเริ่มจาก startSeatNumber)
     * param : concertId, name, price, capacity, color, startRow, startSeatNumber, rowCount, seatsPerRow
     * return : int รหัสโซน
     */
    private function seedZoneWithSeats(int $concertId, string $name, float $price, int $capacity, string $color, string $startRow, int $startSeatNumber, int $rowCount, int $seatsPerRow): int
    {
        $zoneId = DB::table('zone')->insertGetId([
            'z_concert_id' => $concertId,
            'name' => $name,
            'zone_type' => 'SEATED',
            'price' => $price,
            'capacity' => $capacity,
            'total_rows' => $rowCount,
            'seats_per_row' => $seatsPerRow,
            'seat_number_start' => $startSeatNumber,
            'row_alphabet_start' => $startRow,
            'color' => $color,
            'created_at' => now(),
        ]);

        $seats = [];
        for ($rowOffset = 0; $rowOffset < $rowCount; $rowOffset++) {
            $row = chr(ord($startRow) + $rowOffset);
            for ($seatNumber = $startSeatNumber; $seatNumber < $startSeatNumber + $seatsPerRow; $seatNumber++) {
                $seats[] = [
                    's_zone_id' => $zoneId,
                    'row' => $row,
                    'number' => $seatNumber,
                    'seat_booking_status' => 'available',
                    'seat_status' => 'active',
                ];
            }
        }

        DB::table('seat')->insert($seats);

        return $zoneId;
    }

    /**
     * seedStandingZone(concertId, name, price, capacity, color)
     * สร้างโซนยืน (STANDING) พร้อมแถวตัวนับใน zone_inventory (held = 0, sold = 0)
     * param : concertId, name, price, capacity, color
     * return : int รหัสโซน
     */
    private function seedStandingZone(int $concertId, string $name, float $price, int $capacity, string $color): int
    {
        $zoneId = DB::table('zone')->insertGetId([
            'z_concert_id' => $concertId,
            'name' => $name,
            'zone_type' => 'STANDING',
            'price' => $price,
            'capacity' => $capacity,
            'color' => $color,
            'created_at' => now(),
        ]);

        DB::table('zone_inventory')->insert(['zi_zone_id' => $zoneId, 'held' => 0, 'sold' => 0]);

        return $zoneId;
    }

    /**
     * seedPaymentOrderAndTickets(bookingId, seatIds, amount, concertCode)
     * สร้างการชำระเงินที่สำเร็จ คำสั่งซื้อ (พร้อม snapshot) และบัตร 1 ใบต่อที่นั่ง ของการจองที่กำหนด
     * param : bookingId, seatIds, amount, concertCode
     * return : void
     */
    private function seedPaymentOrderAndTickets(int $bookingId, array $seatIds, float $amount, string $concertCode): void
    {
        $booking = DB::table('booking')->where('b_id', $bookingId)->first();
        $zone = DB::table('zone')->where('z_id', $booking->b_zone_id)->first();
        $concert = DB::table('concert')->where('c_id', $zone->z_concert_id)->first();
        $org = DB::table('organization')->where('org_id', $concert->c_organization_id)->first();
        $user = DB::table('app_user')->where('u_id', $booking->b_user_id)->first();

        $paymentId = DB::table('payment')->insertGetId([
            'p_booking_id' => $bookingId,
            'amount' => $amount,
            'method' => 'CREDIT_CARD',
            'status' => 'COMPLETED',
            'provider_transaction_id' => 'ch_' . strtolower(Str::random(24)),
        ]);

        $orderId = DB::table('user_order')->insertGetId([
            'order_code' => $this->makeCode('ORD', $concertCode),
            'uo_booking_id' => $bookingId,
            'uo_payment_id' => $paymentId,
            'concert_name_snapshot' => $concert->name,
            'artist_snapshot' => $concert->artist,
            'venue_snapshot' => $concert->venue,
            'event_datetime_snapshot' => $concert->event_datetime,
            'organization_name_snapshot' => $org->name,
            'user_firstname_snapshot' => $user->first_name,
            'user_lastname_snapshot' => $user->last_name,
            'user_phone_snapshot' => $user->phone,
            'user_email_snapshot' => $user->email,
            'zone_name_snapshot' => $booking->zone_name_snapshot,
            'unit_price_snapshot' => $booking->zone_price_snapshot,
            'total_amount_snapshot' => $booking->total_amount,
            'quantity' => count($seatIds),
        ]);

        foreach (array_values($seatIds) as $index => $seatId) {
            $seat = DB::table('seat')->where('s_id', $seatId)->first();

            DB::table('ticket')->insert([
                'ticket_code' => $this->makeCode('TKT', $concertCode),
                't_order_id' => $orderId,
                't_zone_id' => $booking->b_zone_id,
                't_seat_id' => $seatId,
                'qr_code' => hash('sha256', uniqid() . $orderId . config('app.key', 'dev-key')),
                'is_used' => 0,
                'seat_label_snapshot' => $seat ? $seat->row . $seat->number : null,
                'status' => 'valid',
                'slot_no' => $index + 1,
            ]);
        }
    }

    /**
     * makeCode(prefix, concertCode)
     * สร้างรหัสตาม Public Code Format Specification V2.0.0: {TYPE}-{CONCERT 7 ตัว}-{ลำดับ 6 หลัก}
     * ถ้าไม่ระบุ concertCode จะได้รหัสสุ่ม {TYPE}-{10 ตัว}
     * param : prefix, concertCode
     * return : string
     */
    private function makeCode(string $prefix, ?string $concertCode = null): string
    {
        $parts = [$prefix];

        // นับลำดับแยกตาม TYPE + คอนเสิร์ต ไม่มีวันที่
        if ($concertCode) {
            $parts[] = \App\Models\Concert::normalizePublicCode($concertCode);
            $sequenceKey = implode('-', $parts);
            $this->codeSequences[$sequenceKey] = ($this->codeSequences[$sequenceKey] ?? 0) + 1;
            $parts[] = str_pad((string) $this->codeSequences[$sequenceKey], 6, '0', STR_PAD_LEFT);

            return implode('-', $parts);
        }

        return $prefix . '-' . strtoupper(Str::random(10));
    }
}
