<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    private array $codeSequences = [];

    public function run(): void
    {
        $this->truncateNewSchema();

        $now = now();

        // Organizations (formerly company)
        $orgGmmId = DB::table('organization')->insertGetId([
            'name' => 'GMM Grammy',
            'initials' => 'GMM',
            'description' => 'Thai entertainment company and concert promoter.',
            'phone' => '02-669-9000',
            'email' => 'contact@gmm-grammy.example',
            'facebook' => 'GMM Grammy Official',
            'line' => '@gmmgrammy',
            'created_at' => $now,
        ]);

        $orgBecId = DB::table('organization')->insertGetId([
            'name' => 'BEC-Tero Entertainment',
            'initials' => 'BEC',
            'description' => 'Live event and concert promoter.',
            'phone' => '02-612-9999',
            'email' => 'contact@bec-tero.example',
            'facebook' => 'BEC-Tero Entertainment',
            'line' => '@bectero',
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
            'organization_id' => $orgGmmId,
            'created_at' => $now,
        ]);

        $orgBecUserId = DB::table('app_user')->insertGetId([
            'email' => 'organizer@bec.local',
            'password_hash' => Hash::make('Password123!'),
            'role' => 'ORGANIZER',
            'account_status' => 1,
            'username' => 'bec-organizer',
            'organization_id' => $orgBecId,
            'created_at' => $now,
        ]);

        $staffGmm1Id = DB::table('app_user')->insertGetId([
            'password_hash' => Hash::make('Password123!'),
            'role' => 'STAFF',
            'account_status' => 1,
            'username' => 'gmm-gate-1',
            'organization_id' => $orgGmmId,
            'created_by_user_id' => $orgGmmUserId,
            'created_at' => $now,
        ]);

        $staffGmm2Id = DB::table('app_user')->insertGetId([
            'password_hash' => Hash::make('Password123!'),
            'role' => 'STAFF',
            'account_status' => 1,
            'username' => 'gmm-gate-2',
            'organization_id' => $orgGmmId,
            'created_by_user_id' => $orgGmmUserId,
            'created_at' => $now,
        ]);

        $staffBec1Id = DB::table('app_user')->insertGetId([
            'password_hash' => Hash::make('Password123!'),
            'role' => 'STAFF',
            'account_status' => 1,
            'username' => 'bec-gate-1',
            'organization_id' => $orgBecId,
            'created_by_user_id' => $orgBecUserId,
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

        $user2Id = DB::table('app_user')->insertGetId([
            'email' => 'user2@example.com',
            'password_hash' => Hash::make('Password123!'),
            'role' => 'USER',
            'account_status' => 1,
            'first_name' => 'Wichai',
            'last_name' => 'Rungrueang',
            'phone' => '0812345002',
            'created_at' => $now,
        ]);

        // Concerts
        $concertRockId = DB::table('concert')->insertGetId([
            'organization_id' => $orgGmmId,
            'name' => 'Rock Night 2026',
            'artist' => 'Bodyslam',
            'venue' => 'BCC Hall, Bangkok',
            'event_datetime' => '2026-11-01 18:00:00',
            'description' => 'A high-energy rock concert in Bangkok.',
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
            'organization_id' => $orgBecId,
            'name' => 'Pop Star Live 2026',
            'artist' => 'Bird Thongchai',
            'venue' => 'Impact Arena, Muang Thong Thani',
            'event_datetime' => '2026-12-15 19:00:00',
            'description' => 'A pop concert with reserved and standing zones.',
            'poster_path' => 'posters/pop-star-live-2026.jpg',
            'seat_map_image_path' => 'seat-maps/pop-star-live-2026.png',
            'sale_start_datetime' => '2026-10-01 10:00:00',
            'sale_end_datetime' => '2026-12-14 23:59:59',
            'max_ticket_per_order' => 6,
            'status' => 'SCHEDULED',
            'concert_code' => 'C0025',
            'created_at' => $now,
        ]);

        $rockVipZoneId = $this->seedZoneWithSeats($concertRockId, 'VIP', 'SEATED', 3000, 15, '#F59E0B', 'A', 1, 3, 5);
        $rockZoneAId = $this->seedZoneWithSeats($concertRockId, 'Zone A', 'SEATED', 1500, 24, '#3B82F6', 'D', 1, 4, 6);
        $popStandingZoneId = $this->seedStandingZone($concertPopId, 'Standing', 1200, 500, '#10B981');

        DB::table('staff_concert')->insert([
            ['staff_id' => $staffGmm1Id, 'concert_id' => $concertRockId],
            ['staff_id' => $staffGmm2Id, 'concert_id' => $concertRockId],
            ['staff_id' => $staffBec1Id, 'concert_id' => $concertPopId],
        ]);

        $vipSeats = DB::table('seat')->where('zone_id', $rockVipZoneId)->orderBy('row')->orderBy('number')->take(2)->get();
        
        $seatBookingId = DB::table('booking')->insertGetId([
            'booking_code' => $this->makeCode('BKG'),   // booking_code เป็นรหัสภายใน (ไม่ใช่ Public Code)
            'user_id' => $user1Id,
            'concert_id' => $concertRockId,
            'booking_date' => $now->copy()->subMinutes(30),
            'total_amount' => 6000,
            'status' => 'CONFIRMED',
            'expiry_time' => $now->copy()->subMinutes(20),
        ]);

        foreach ($vipSeats as $seat) {
            DB::table('booking_seat')->insert([
                'booking_id' => $seatBookingId,
                'seat_id' => $seat->seat_id,
                'zone_name_snapshot' => 'VIP',
                'seat_label_snapshot' => $seat->row . $seat->number,
                'unit_price_snapshot' => 3000,
            ]);
            DB::table('seat')->where('seat_id', $seat->seat_id)->update(['seat_booking_status' => 'SOLD']);
        }

        $this->seedPaymentOrderAndTickets(
            $seatBookingId,
            $concertRockId,
            $rockVipZoneId,
            $vipSeats->pluck('seat_id')->all(),
            6000,
            'C024'
        );

        $this->command->info('Seeded the v3.1.0 SQL schema successfully.');
    }

    private function truncateNewSchema(): void
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

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function seedZoneWithSeats(int $concertId, string $name, string $zoneType, float $price, int $capacity, string $color, string $startRow, int $startSeatNumber, int $rowCount, int $seatsPerRow): int
    {
        $zoneId = DB::table('zone')->insertGetId([
            'concert_id' => $concertId,
            'name' => $name,
            'zone_type' => $zoneType,
            'price' => $price,
            'capacity' => $capacity,
            'color' => $color,
            'created_at' => now(),
        ]);

        $seats = [];
        for ($rowOffset = 0; $rowOffset < $rowCount; $rowOffset++) {
            $row = chr(ord($startRow) + $rowOffset);
            for ($seatNumber = $startSeatNumber; $seatNumber < $startSeatNumber + $seatsPerRow; $seatNumber++) {
                $seats[] = [
                    'zone_id' => $zoneId,
                    'row' => $row,
                    'number' => $seatNumber,
                    'seat_booking_status' => 'AVAILABLE',
                    'seat_status' => 'NORMAL',
                ];
            }
        }

        DB::table('seat')->insert($seats);

        return $zoneId;
    }

    private function seedStandingZone(int $concertId, string $name, float $price, int $capacity, string $color): int
    {
        return DB::table('zone')->insertGetId([
            'concert_id' => $concertId,
            'name' => $name,
            'zone_type' => 'STANDING',
            'price' => $price,
            'capacity' => $capacity,
            'color' => $color,
            'created_at' => now(),
        ]);
    }

    private function seedPaymentOrderAndTickets(int $bookingId, int $concertId, int $zoneId, array $seatIds, float $amount, string $concertCode): void
    {
        $concert = DB::table('concert')->where('concert_id', $concertId)->first();
        $org = DB::table('organization')->where('organization_id', $concert->organization_id)->first();
        $zone = DB::table('zone')->where('zone_id', $zoneId)->first();
        $booking = DB::table('booking')->where('booking_id', $bookingId)->first();
        $user = DB::table('app_user')->where('user_id', $booking->user_id)->first();
        $unitPrice = $zone ? (float) $zone->price : 0;

        $paymentId = DB::table('payment')->insertGetId([
            'booking_id' => $bookingId,
            'amount' => $amount,
            'method' => 'CREDIT_CARD',
            'status' => 'COMPLETED',
            'provider_transaction_id' => 'ch_' . strtolower(Str::random(24)),
        ]);

        $orderId = DB::table('user_order')->insertGetId([
            'order_code' => $this->makeCode('ORD', $concertCode),
            'booking_id' => $bookingId,
            'payment_id' => $paymentId,
            'concert_name_snapshot' => $concert->name,
            'artist_snapshot' => $concert->artist,
            'venue_snapshot' => $concert->venue,
            'event_datetime_snapshot' => $concert->event_datetime,
            'organization_name_snapshot' => $org->name,
            'user_fiestname_snapshot' => $user->first_name,
            'user_lastname_snapshot' => $user->last_name,
            'user_phone_snapshot' => $user->phone,
        ]);

        foreach ($seatIds as $seatId) {
            $seat = $seatId ? DB::table('seat')->where('seat_id', $seatId)->first() : null;

            DB::table('ticket')->insert([
                'ticket_code' => $this->makeCode('TKT', $concertCode),
                'order_id' => $orderId,
                'zone_id' => $zoneId,
                'seat_id' => $seatId,
                'qr_code' => hash('sha256', uniqid() . $orderId . config('app.key', 'dev-key')),
                'is_used' => 0,
                'zone_name_snapshot' => $zone->name,
                'seat_label_snapshot' => $seat ? $seat->row . $seat->number : null,
                'unit_price_snapshot' => $unitPrice,
            ]);
        }
    }

    private function makeCode(string $prefix, ?string $concertCode = null): string
    {
        $parts = [$prefix];

        // Public Code Format Specification V2.0.0: {TYPE}-{CONCERT 7 ตัว}-{ลำดับ 6 หลัก} นับแยกตาม TYPE + คอนเสิร์ต ไม่มีวันที่
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

