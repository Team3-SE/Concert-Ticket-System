<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Seat;
use App\Models\SeatLockLog;
use App\Models\BookingSeat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * BookingController  (Customer only)
 * Methods: index(), validateBookingSecurity(), createBooking(), getBookingHistory()
 *
 * NOTE – Organizer-only methods ที่ถูกย้ายออก:
 *   getCustomerContact() → OrganizerBookingController (สร้างใหม่ภายหลัง)
 *
 * Author : AI Agent | Updated : 27/09/2026
 */
class BookingController extends Controller
{
    /**
     * index() – แสดงหน้า Booking (UI)
     */
    public function index(Request $request)
    {
        $concertController = new ConcertController();
        $concertId = (int) $request->query('concert_id');

        $concert = $concertId
            ? $concertController->getConcertDetail($concertId)
            : \App\Models\Concert::with(['organization', 'zones'])
                ->where('status', 'ON_SALE')
                ->where('is_deleted', 0)
                ->orderBy('event_datetime', 'asc')
                ->first();

        if (!$concert) {
            return view('booking', [
                'concert'       => null,
                'zones'         => collect(),
                'occupiedSeats' => [],
            ]);
        }

        $zones = $concert->zones->map(function ($zone) {
            $availableCount = $zone->zone_type !== 'STANDING'
                ? $zone->getAvailableSeats()->count()
                : $zone->getAvailableCapacity();

            return [
                'zone_id'   => $zone->zone_id,
                'name'      => $zone->name,
                'zone_type' => $zone->zone_type,
                'price'     => (int) $zone->price,
                'seats'     => $availableCount,
                'color'     => $zone->color,
            ];
        });

        // ที่นั่งที่ถูกจองแล้ว (LOCKED, RESERVED, SOLD)
        $occupiedSeats = [];
        foreach ($concert->zones as $zone) {
            if ($zone->zone_type !== 'STANDING') {
                $zone->seats()
                    ->whereIn('seat_booking_status', ['LOCKED', 'RESERVED', 'SOLD'])
                    ->get(['row', 'number'])
                    ->each(function ($seat) use (&$occupiedSeats) {
                        $occupiedSeats[] = $seat->row . $seat->number;
                    });
            }
        }

        return view('booking', compact('concert', 'zones', 'occupiedSeats'));
    }

    /**
     * validateBookingSecurity(customerID, deviceFingerprint, captchaToken): Boolean
     * ตรวจ device fingerprint และ CAPTCHA ก่อนสร้าง Booking
     * ปฏิเสธคำขอเมื่อบัญชีมี accountStatus เป็น BANNED หรือมีพฤติกรรมเสี่ยง
     */
    public function validateBookingSecurity(int $customerId, ?string $deviceFingerprint, ?string $captchaToken): bool
    {
        $user = \App\Models\AppUser::find($customerId);
        if (!$user || $user->isBanned()) {
            return false;
        }

        // TODO: ตรวจสอบ CAPTCHA กับ provider จริง (reCAPTCHA / hCaptcha)
        // TODO: ตรวจสอบ device fingerprint กับ blacklist

        return true;
    }

    /**
     * createBooking(customerID, concertID, seats, standingItems): Booking
     * ตรวจ security → สร้าง Booking → ล็อก Seat → บันทึก BookingSeat พร้อม Snapshot
     */
    public function createBooking(int $customerId, int $concertId, array $seatIds = [], array $standingItems = []): Booking
    {
        if (!$this->validateBookingSecurity($customerId, null, null)) {
            abort(403, 'บัญชีถูกระงับหรือพฤติกรรมไม่ได้รับอนุญาต');
        }

        return DB::transaction(function () use ($customerId, $concertId, $seatIds, $standingItems) {
            $concert = \App\Models\Concert::with('zones')->findOrFail($concertId);
            $totalAmount = 0;

            // ── คำนวณยอดรวม SEATED ──
            $seatsData = [];
            foreach ($seatIds as $seatId) {
                $seat = Seat::with('zone')->findOrFail($seatId);
                if ($seat->seat_booking_status !== 'AVAILABLE') {
                    abort(409, "Seat {$seatId} ไม่ว่าง");
                }
                $totalAmount += (float) $seat->zone->price;
                $seatsData[] = [
                    'seat'       => $seat,
                    'zone'       => $seat->zone,
                    'unit_price' => (float) $seat->zone->price,
                ];
            }

            // ── คำนวณยอดรวม STANDING ──
            foreach ($standingItems as $item) {
                $zone = \App\Models\Zone::findOrFail($item['zone_id']);
                $qty  = (int) ($item['quantity'] ?? 1);
                if ($zone->getAvailableCapacity() < $qty) {
                    abort(409, "Zone {$zone->name} capacity ไม่เพียงพอ");
                }
                $totalAmount += (float) $zone->price * $qty;
            }

            // ── สร้าง Booking ──
            $booking = Booking::create([
                'booking_code'  => 'BKG-' . strtoupper(Str::random(10)),
                'customer_id'   => $customerId,
                'concert_id'    => $concertId,
                'booking_date'  => now(),
                'total_amount'  => $totalAmount,
                'status'        => 'PENDING_PAYMENT',
                'expiry_time'   => now()->addMinutes(15),
            ]);

            // ── ล็อก Seat + บันทึก BookingSeat + SeatLockLog ──
            foreach ($seatsData as $sd) {
                $sd['seat']->lockSeat();

                BookingSeat::create([
                    'booking_id'           => $booking->booking_id,
                    'seat_id'              => $sd['seat']->seat_id,
                    'zone_name_snapshot'   => $sd['zone']->name,
                    'seat_label_snapshot'  => $sd['seat']->row . $sd['seat']->number,
                    'unit_price_snapshot'  => $sd['unit_price'],
                ]);

                SeatLockLog::create([
                    'booking_id' => $booking->booking_id,
                    'seat_id'    => $sd['seat']->seat_id,
                    'locked_at'  => now(),
                    'expired_at' => now()->addMinutes(15),
                    'status'     => 'LOCKED',
                ]);
            }

            return $booking;
        });
    }

    /**
     * getBookingHistory(customerID): List<Booking>
     * ดึงประวัติ Booking ของ Customer
     * ใช้: Customer (BookingHistoryInterface)
     */
    public function getBookingHistory(int $customerId)
    {
        return Booking::with(['concert', 'bookingSeats', 'customerOrder'])
            ->where('customer_id', $customerId)
            ->orderBy('booking_date', 'desc')
            ->get();
    }
}

