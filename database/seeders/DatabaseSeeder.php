<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->truncateNewSchema();

        $now = now();
        $ids = [
            'company_gmm' => (string) Str::ulid(),
            'company_bec' => (string) Str::ulid(),
            'admin' => (string) Str::ulid(),
            'organizer_gmm' => (string) Str::ulid(),
            'organizer_bec' => (string) Str::ulid(),
            'staff_gmm_1' => (string) Str::ulid(),
            'staff_gmm_2' => (string) Str::ulid(),
            'staff_bec_1' => (string) Str::ulid(),
            'customer_1' => (string) Str::ulid(),
            'customer_2' => (string) Str::ulid(),
            'customer_3' => (string) Str::ulid(),
            'concert_rock' => (string) Str::ulid(),
            'concert_pop' => (string) Str::ulid(),
        ];

        DB::table('company')->insert([
            [
                'company_id' => $ids['company_gmm'],
                'name' => 'GMM Grammy',
                'initials' => 'GMM',
                'description' => 'Thai entertainment company and concert promoter.',
                'created_at' => $now,
            ],
            [
                'company_id' => $ids['company_bec'],
                'name' => 'BEC-Tero Entertainment',
                'initials' => 'BEC',
                'description' => 'Live event and concert promoter.',
                'created_at' => $now,
            ],
        ]);

        DB::table('app_user')->insert([
            $this->appUser($ids['admin'], 'admin'),
            $this->appUser($ids['organizer_gmm'], 'organizer'),
            $this->appUser($ids['organizer_bec'], 'organizer'),
            $this->appUser($ids['staff_gmm_1'], 'staff'),
            $this->appUser($ids['staff_gmm_2'], 'staff'),
            $this->appUser($ids['staff_bec_1'], 'staff'),
            $this->appUser($ids['customer_1'], 'customer'),
            $this->appUser($ids['customer_2'], 'customer'),
            $this->appUser($ids['customer_3'], 'customer'),
        ]);

        DB::table('admin')->insert([
            'admin_id' => $ids['admin'],
            'email' => 'admin@concert.local',
            'username' => 'admin',
        ]);

        DB::table('event_organizer')->insert([
            [
                'organizer_id' => $ids['organizer_gmm'],
                'company_id' => $ids['company_gmm'],
                'email' => 'organizer@gmm.local',
                'username' => 'gmm-organizer',
            ],
            [
                'organizer_id' => $ids['organizer_bec'],
                'company_id' => $ids['company_bec'],
                'email' => 'organizer@bec.local',
                'username' => 'bec-organizer',
            ],
        ]);

        DB::table('staff')->insert([
            [
                'staff_id' => $ids['staff_gmm_1'],
                'company_id' => $ids['company_gmm'],
                'created_by_organizer_id' => $ids['organizer_gmm'],
                'username' => 'gmm-gate-1',
            ],
            [
                'staff_id' => $ids['staff_gmm_2'],
                'company_id' => $ids['company_gmm'],
                'created_by_organizer_id' => $ids['organizer_gmm'],
                'username' => 'gmm-gate-2',
            ],
            [
                'staff_id' => $ids['staff_bec_1'],
                'company_id' => $ids['company_bec'],
                'created_by_organizer_id' => $ids['organizer_bec'],
                'username' => 'bec-gate-1',
            ],
        ]);

        DB::table('customer')->insert([
            [
                'customer_id' => $ids['customer_1'],
                'email' => 'customer1@example.com',
                'phone' => '0812345001',
                'first_name' => 'Suda',
                'last_name' => 'Saengdao',
            ],
            [
                'customer_id' => $ids['customer_2'],
                'email' => 'customer2@example.com',
                'phone' => '0812345002',
                'first_name' => 'Wichai',
                'last_name' => 'Rungrueang',
            ],
            [
                'customer_id' => $ids['customer_3'],
                'email' => 'customer3@example.com',
                'phone' => '0812345003',
                'first_name' => 'Napa',
                'last_name' => 'Thongdee',
            ],
        ]);

        DB::table('concert')->insert([
            [
                'concert_id' => $ids['concert_rock'],
                'company_id' => $ids['company_gmm'],
                'organizer_id' => $ids['organizer_gmm'],
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
                'status' => 'published',
            ],
            [
                'concert_id' => $ids['concert_pop'],
                'company_id' => $ids['company_bec'],
                'organizer_id' => $ids['organizer_bec'],
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
                'status' => 'published',
            ],
        ]);

        DB::table('concert_contact')->insert([
            [
                'contact_id' => (string) Str::ulid(),
                'concert_id' => $ids['concert_rock'],
                'contact_type' => 'email',
                'value' => 'support@gmm.local',
            ],
            [
                'contact_id' => (string) Str::ulid(),
                'concert_id' => $ids['concert_pop'],
                'contact_type' => 'phone',
                'value' => '021234567',
            ],
        ]);

        $rockVipZone = $this->seedZoneWithSeats($ids['concert_rock'], 'VIP', 'seated', 3000, 15, '#F59E0B', 'A', 1, 3, 5);
        $rockZoneA = $this->seedZoneWithSeats($ids['concert_rock'], 'Zone A', 'seated', 1500, 24, '#3B82F6', 'D', 1, 4, 6);
        $popStandingZone = $this->seedStandingZone($ids['concert_pop'], 'Standing', 1200, 500, '#10B981');

        DB::table('staff_concert')->insert([
            ['staff_id' => $ids['staff_gmm_1'], 'concert_id' => $ids['concert_rock']],
            ['staff_id' => $ids['staff_gmm_2'], 'concert_id' => $ids['concert_rock']],
            ['staff_id' => $ids['staff_bec_1'], 'concert_id' => $ids['concert_pop']],
        ]);

        $vipSeats = DB::table('seat')->where('zone_id', $rockVipZone)->orderBy('row')->orderBy('number')->take(2)->get();
        $seatBookingId = (string) Str::ulid();
        $seatPaymentId = (string) Str::ulid();
        $seatOrderId = (string) Str::ulid();

        DB::table('booking')->insert([
            'booking_id' => $seatBookingId,
            'booking_code' => $this->makeCode('BKG'),
            'customer_id' => $ids['customer_1'],
            'concert_id' => $ids['concert_rock'],
            'booking_date' => $now->copy()->subMinutes(30),
            'total_amount' => 6000,
            'status' => 'confirmed',
            'expiry_time' => $now->copy()->subMinutes(20),
        ]);

        foreach ($vipSeats as $seat) {
            DB::table('booking_seat')->insert([
                'booking_id' => $seatBookingId,
                'seat_id' => $seat->seat_id,
            ]);
            DB::table('seat')->where('seat_id', $seat->seat_id)->update(['status' => 'booked']);
        }

        $this->seedPaymentOrderAndTickets(
            $seatPaymentId,
            $seatOrderId,
            $seatBookingId,
            $ids['concert_rock'],
            $rockVipZone,
            $vipSeats->pluck('seat_id')->all(),
            6000,
        );

        $standingBookingId = (string) Str::ulid();
        $standingPaymentId = (string) Str::ulid();
        $standingOrderId = (string) Str::ulid();

        DB::table('booking')->insert([
            'booking_id' => $standingBookingId,
            'booking_code' => $this->makeCode('BKG'),
            'customer_id' => $ids['customer_2'],
            'concert_id' => $ids['concert_pop'],
            'booking_date' => $now->copy()->subMinutes(45),
            'total_amount' => 3600,
            'status' => 'confirmed',
            'expiry_time' => $now->copy()->subMinutes(35),
        ]);

        DB::table('booking_standing_item')->insert([
            'booking_id' => $standingBookingId,
            'zone_id' => $popStandingZone,
            'quantity' => 3,
        ]);

        $this->seedPaymentOrderAndTickets(
            $standingPaymentId,
            $standingOrderId,
            $standingBookingId,
            $ids['concert_pop'],
            $popStandingZone,
            [null, null, null],
            3600,
        );

        DB::table('seat_lock_log')->insert([
            'lock_id' => (string) Str::ulid(),
            'booking_id' => $seatBookingId,
            'seat_id' => $vipSeats[0]->seat_id,
            'locked_at' => $now->copy()->subMinutes(40),
            'expired_at' => $now->copy()->subMinutes(30),
            'released_at' => $now->copy()->subMinutes(29),
            'status' => 'released',
        ]);

        DB::table('ticket_validation_log')->insert([
            'validation_id' => (string) Str::ulid(),
            'ticket_id' => DB::table('ticket')->where('order_id', $seatOrderId)->value('ticket_id'),
            'staff_id' => $ids['staff_gmm_1'],
            'validation_time' => $now->copy()->subMinutes(5),
            'result' => true,
        ]);

        $anomalyId = (string) Str::ulid();
        DB::table('system_anomaly')->insert([
            'anomaly_id' => $anomalyId,
            'anomaly_code' => $this->makeCode('ERR'),
            'booking_id' => $standingBookingId,
            'reviewed_by_admin_id' => $ids['admin'],
            'type' => 'suspicious_payment',
            'message' => 'Multiple standing tickets purchased from a watched payment fingerprint.',
            'timestamp' => $now->copy()->subMinutes(10),
            'status' => 'reviewing',
            'resolve_reason' => null,
        ]);

        DB::table('anomaly_attachment')->insert([
            'attachment_id' => (string) Str::ulid(),
            'anomaly_id' => $anomalyId,
            'file_path' => 'anomaly-attachments/payment-risk-sample.json',
        ]);

        DB::table('audit_log')->insert([
            [
                'log_id' => (string) Str::ulid(),
                'user_id' => $ids['customer_1'],
                'action' => 'booking_confirmed',
                'timestamp' => $now->copy()->subMinutes(25),
                'ip_address' => '127.0.0.1',
            ],
            [
                'log_id' => (string) Str::ulid(),
                'user_id' => $ids['admin'],
                'action' => 'anomaly_review_started',
                'timestamp' => $now->copy()->subMinutes(8),
                'ip_address' => '127.0.0.1',
            ],
        ]);

        $this->command->info('Seeded the new ERD schema.');
    }

    private function truncateNewSchema(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

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
            if (! Schema::hasTable($table)) {
                continue;
            }

            if ($driver === 'sqlite') {
                DB::table($table)->delete();
            } else {
                DB::table($table)->truncate();
            }
        }

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function appUser(string $userId, string $role): array
    {
        return [
            'user_id' => $userId,
            'password_hash' => Hash::make('Password123!'),
            'role' => $role,
            'account_status' => 'active',
            'created_at' => now(),
        ];
    }

    private function seedZoneWithSeats(
        string $concertId,
        string $name,
        string $zoneType,
        float $price,
        int $capacity,
        string $color,
        string $startRow,
        int $startSeatNumber,
        int $rowCount,
        int $seatsPerRow
    ): string {
        $zoneId = (string) Str::ulid();

        DB::table('zone')->insert([
            'zone_id' => $zoneId,
            'concert_id' => $concertId,
            'name' => $name,
            'zone_type' => $zoneType,
            'price' => $price,
            'capacity' => $capacity,
            'color' => $color,
        ]);

        DB::table('zone_seat_pattern')->insert([
            'pattern_id' => (string) Str::ulid(),
            'zone_id' => $zoneId,
            'pattern_type' => 'grid',
            'start_row' => $startRow,
            'start_seat_number' => $startSeatNumber,
            'row_count' => $rowCount,
            'seats_per_row' => $seatsPerRow,
        ]);

        $seats = [];
        for ($rowOffset = 0; $rowOffset < $rowCount; $rowOffset++) {
            $row = chr(ord($startRow) + $rowOffset);
            for ($seatNumber = $startSeatNumber; $seatNumber < $startSeatNumber + $seatsPerRow; $seatNumber++) {
                $seats[] = [
                    'seat_id' => (string) Str::ulid(),
                    'zone_id' => $zoneId,
                    'row' => $row,
                    'number' => $seatNumber,
                    'status' => 'available',
                ];
            }
        }

        DB::table('seat')->insert($seats);

        return $zoneId;
    }

    private function seedStandingZone(string $concertId, string $name, float $price, int $capacity, string $color): string
    {
        $zoneId = (string) Str::ulid();

        DB::table('zone')->insert([
            'zone_id' => $zoneId,
            'concert_id' => $concertId,
            'name' => $name,
            'zone_type' => 'standing',
            'price' => $price,
            'capacity' => $capacity,
            'color' => $color,
        ]);

        return $zoneId;
    }

    private function seedPaymentOrderAndTickets(
        string $paymentId,
        string $orderId,
        string $bookingId,
        string $concertId,
        string $zoneId,
        array $seatIds,
        float $amount
    ): void {
        DB::table('payment')->insert([
            'payment_id' => $paymentId,
            'booking_id' => $bookingId,
            'amount' => $amount,
            'method' => 'credit_card',
            'status' => 'success',
            'payment_date' => now()->subMinutes(20),
            'provider_transaction_id' => 'ch_' . strtolower(Str::random(24)),
            'decline_code' => null,
            'risk_level' => 'low',
            'card_fingerprint' => strtolower(Str::random(32)),
        ]);

        DB::table('customer_order')->insert([
            'order_id' => $orderId,
            'order_code' => $this->makeCode('ORD'),
            'booking_id' => $bookingId,
            'payment_id' => $paymentId,
            'order_date' => now()->subMinutes(18),
        ]);

        foreach ($seatIds as $seatId) {
            $ticketId = (string) Str::ulid();

            DB::table('ticket')->insert([
                'ticket_id' => $ticketId,
                'ticket_code' => $this->makeCode('TKT'),
                'order_id' => $orderId,
                'concert_id' => $concertId,
                'zone_id' => $zoneId,
                'seat_id' => $seatId,
                'qr_code' => hash('sha256', $ticketId . $orderId . config('app.key', 'dev-key')),
                'is_used' => false,
            ]);
        }
    }

    private function makeCode(string $prefix): string
    {
        return $prefix . '-' . strtoupper(Str::random(10));
    }
}
