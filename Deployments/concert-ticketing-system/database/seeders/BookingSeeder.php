<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * BookingSeeder
 * seed 3 confirmed bookings with payments and e-tickets for Rock Night 2026 VIP
 * Author : Squad C (Booking) & Squad D (Payment) & Squad E (Ticket)
 * Created : 28-08-2569
 */
class BookingSeeder extends Seeder
{
    /**
     * run()
     * create confirmed bookings with payments, seat_lock_logs, and tickets
     * param : none
     * return : void
     */
    public function run(): void
    {
        $customers = DB::table('cts_customers')->take(3)->get();
        $concert   = DB::table('cts_concerts')->where('name', 'Rock Night 2026')->first();
        $vipZone   = DB::table('cts_zones')
                       ->where('concert_id', $concert->concert_id)
                       ->where('name', 'VIP')->first();
        $vipSeats  = DB::table('cts_seats')
                       ->where('zone_id', $vipZone->zone_id)
                       ->take(3)->get();

        $count = 0;
        foreach ($vipSeats as $i => $seat) {
            $customer  = $customers[$i];
            $bookingId = (string) Str::uuid();
            $paymentId = (string) Str::uuid();
            $ticketId  = (string) Str::uuid();

            // Booking
            DB::table('cts_bookings')->insert([
                'booking_id'   => $bookingId,
                'customer_id'  => $customer->customer_id,
                'concert_id'   => $concert->concert_id,
                'booking_date' => now()->subMinutes(30),
                'total_amount' => $vipZone->price,
                'status'       => 'confirmed',
                'expiry_time'  => now()->subMinutes(20),
                'created_at'   => now()->subMinutes(30),
                'updated_at'   => now(),
            ]);

            // Booking seat
            DB::table('cts_booking_seats')->insert([
                'booking_id' => $bookingId, 'seat_id' => $seat->seat_id,
                'created_at' => now()->subMinutes(30), 'updated_at' => now()->subMinutes(30),
            ]);

            // Payment
            DB::table('cts_payments')->insert([
                'payment_id'              => $paymentId,
                'booking_id'             => $bookingId,
                'amount'                 => $vipZone->price,
                'method'                 => 'credit_card',
                'status'                 => 'success',
                'payment_date'           => now()->subMinutes(25),
                'provider_transaction_id' => 'ch_test_' . strtolower(Str::random(20)),
                'decline_code'           => null,
                'risk_level'             => 'low',
                'card_fingerprint'       => strtolower(Str::random(32)),
                'created_at'             => now()->subMinutes(30),
                'updated_at'             => now()->subMinutes(25),
            ]);

            // Ticket
            DB::table('cts_tickets')->insert([
                'ticket_id'  => $ticketId,
                'booking_id' => $bookingId,
                'concert_id' => $concert->concert_id,
                'seat_id'    => $seat->seat_id,
                'qr_code'    => hash('sha256', $ticketId . $bookingId . config('app.key', 'dev-key')),
                'is_used'    => false,
                'created_at' => now()->subMinutes(24),
                'updated_at' => now()->subMinutes(24),
            ]);

            // Seat lock log
            DB::table('cts_seat_lock_logs')->insert([
                'lock_id'     => (string) Str::uuid(),
                'booking_id'  => $bookingId,
                'seat_id'     => $seat->seat_id,
                'locked_at'   => now()->subMinutes(35),
                'expired_at'  => now()->subMinutes(25),
                'released_at' => now()->subMinutes(25),
                'status'      => 'released',
                'created_at'  => now()->subMinutes(35),
                'updated_at'  => now()->subMinutes(25),
            ]);

            // Update seat to booked
            DB::table('cts_seats')
              ->where('seat_id', $seat->seat_id)
              ->update(['status' => 'booked', 'updated_at' => now()]);

            $count++;
        }

        $this->command->info("  âœ“ BookingSeeder: {$count} confirmed bookings, payments & tickets");
    }
}
