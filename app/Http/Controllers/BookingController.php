<?php

/**
 * BookingController
 * จัดการการจองบัตรของลูกค้า: หน้าเลือกที่นั่ง ตรวจความปลอดภัย สร้างการจอง และประวัติการจอง
 * Author :
 * Created :
 */

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Seat;
use App\Models\SeatLockLog;
use App\Models\BookingSeat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    /* เวลาที่ล็อกที่นั่งไว้ให้ชำระเงิน (นาที) */
    private const SEAT_LOCK_MINUTES = 15;

    /**
     * index(request)
     * แสดงหน้าเลือกโซน/ที่นั่งของคอนเสิร์ต พร้อมจำนวนที่ว่างและที่นั่งที่ถูกจองแล้ว
     * param : request
     * return : View booking.booking
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
            return view('booking.booking', [
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

        return view('booking.booking', compact('concert', 'zones', 'occupiedSeats'));
    }

    /**
     * validateBookingSecurity(customerId, deviceFingerprint, captchaToken)
     * ตรวจความปลอดภัยก่อนสร้างการจอง (บัญชีถูกระงับ, CAPTCHA, device fingerprint)
     * param : customerId, deviceFingerprint, captchaToken
     * return : bool – true ถ้าอนุญาตให้จองได้
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
     * createBooking(customerId, concertId, seatIds, standingItems)
     * สร้างการจอง ล็อกที่นั่ง และบันทึก BookingSeat / SeatLockLog พร้อม snapshot ชื่อโซนและราคา
     * param : customerId, concertId, seatIds, standingItems
     * return : Booking ที่สร้างขึ้น
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
                $quantity = (int) ($item['quantity'] ?? 1);
                if ($zone->getAvailableCapacity() < $quantity) {
                    abort(409, "Zone {$zone->name} capacity ไม่เพียงพอ");
                }
                $totalAmount += (float) $zone->price * $quantity;
            }

            // ── สร้าง Booking ──
            $booking = Booking::create([
                'booking_code'  => 'BKG-' . strtoupper(Str::random(10)),
                'user_id'       => $customerId,
                'concert_id'    => $concertId,
                'booking_date'  => now(),
                'total_amount'  => $totalAmount,
                'status'        => 'PENDING_PAYMENT',
                'expiry_time'   => now()->addMinutes(self::SEAT_LOCK_MINUTES),
            ]);

            // ── ล็อก Seat + บันทึก BookingSeat + SeatLockLog ──
            foreach ($seatsData as $seatData) {
                $seatData['seat']->lockSeat();

                BookingSeat::create([
                    'booking_id'           => $booking->booking_id,
                    'seat_id'              => $seatData['seat']->seat_id,
                    'zone_name_snapshot'   => $seatData['zone']->name,
                    'seat_label_snapshot'  => $seatData['seat']->row . $seatData['seat']->number,
                    'unit_price_snapshot'  => $seatData['unit_price'],
                ]);

                SeatLockLog::create([
                    'booking_id' => $booking->booking_id,
                    'seat_id'    => $seatData['seat']->seat_id,
                    'locked_at'  => now(),
                    'expired_at' => now()->addMinutes(self::SEAT_LOCK_MINUTES),
                    'status'     => 'LOCKED',
                ]);
            }

            return $booking;
        });
    }

    /**
     * getBookingHistory(customerId)
     * ดึงประวัติการจองของลูกค้า เรียงจากล่าสุด
     * param : customerId
     * return : Collection ของ Booking
     */
    public function getBookingHistory(int $customerId)
    {
        return Booking::with(['concert', 'bookingSeats', 'customerOrder'])
            ->where('user_id', $customerId)
            ->orderBy('booking_date', 'desc')
            ->get();
    }
}

