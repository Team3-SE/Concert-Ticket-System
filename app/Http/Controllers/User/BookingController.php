<?php

/**
 * BookingController
 * จัดการการจองบัตรของลูกค้า: หน้าเลือกที่นั่ง ตรวจความปลอดภัย สร้างการจอง และประวัติการจอง
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 04/09/2569
 * Updated : 23/10/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\BookingStandingItem;
use App\Models\Concert;
use App\Models\Seat;
use App\Models\SeatLockLog;
use App\Models\Zone;
use App\Services\SeatLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class BookingController extends Controller
{
    public function __construct(private readonly SeatLockService $seatLockService) {}

    /**
     * index(request)
     * แสดงหน้าเลือกโซน/ที่นั่งของคอนเสิร์ต พร้อมจำนวนที่ว่างและที่นั่งที่ถูกจองแล้ว
     * param : request
     * return : View booking.booking
     */
    public function index(Request $request)
    {
        $concertController = new ConcertController;
        $concertId = (int) $request->query('concert_id');

        $concert = $concertId
            ? $concertController->getConcertDetail($concertId)
            : Concert::with(['organization', 'zones'])
                ->where('status', 'ON_SALE')
                ->where('is_deleted', 0)
                ->orderBy('event_datetime', 'asc')
                ->first();

        // คอนเสิร์ตที่ไม่ได้เปิดขาย (ยังไม่เปิด/ขายหมด/ยกเลิก) เข้าหน้าจองไม่ได้ → กลับไปหน้ารายละเอียด
        if ($concert && $concert->status !== 'ON_SALE') {
            return redirect('/concert-detail?concert_id=' . $concert->concert_id);
        }

        if (! $concert) {
            return view('user.booking.booking', [
                'concert' => null,
                'zones' => collect(),
                'occupiedSeats' => [],
            ]);
        }

        // ครบ 10 นาทีแล้วไม่ชำระ: ปล่อยที่นั่งกลับเป็นว่างก่อนแสดงผังที่นั่ง
        Booking::releaseExpired($concert->concert_id);

        // ที่นั่งที่ผู้ใช้คนนี้ล็อกไว้เองและยังไม่ชำระ: ไม่นับเป็น "คนอื่นกำลังชำระเงิน" (สีส้ม) แต่ยังเลือกซ้ำไม่ได้
        $ownLockedSeatIds = $request->user()
            ? BookingSeat::whereHas('booking', function ($q) use ($request, $concert) {
                $q->where('user_id', $request->user()->user_id)
                    ->where('concert_id', $concert->concert_id)
                    ->where('status', 'PENDING_PAYMENT');
            })->pluck('seat_id')->all()
            : [];

        $occupiedSeats = [];
        $zones = $concert->zones->map(function ($zone) use (&$occupiedSeats, $ownLockedSeatIds) {
            $isStanding = $zone->zone_type === 'STANDING';
            $availableCount = ! $isStanding
                ? $zone->getAvailableSeats()->count()
                : $zone->getAvailableCapacity();

            // ผังที่นั่งจริงของโซน: แถว, เลขที่นั่ง และที่นั่งที่ถูกจองแล้ว (แยกตามโซน)
            $rows = [];
            $cols = [];
            $occupied = [];
            $pending = [];   // ที่นั่งที่คนอื่นล็อกไว้ระหว่างชำระเงิน (LOCKED) แสดงเป็นสีส้ม
            $capacity = (int) $zone->capacity;   // โซนยืน: ความจุตามที่ตั้งไว้ในโซน (โซนนั่งนับจากจำนวนที่นั่งด้านล่าง)
            if (! $isStanding) {
                $seats = $zone->seats()->get(['seat_id', 'row', 'number', 'seat_booking_status']);
                $rows = $seats->pluck('row')->unique()->sort()->values()->all();
                $cols = $seats->pluck('number')->unique()->sort()->values()->all();
                $capacity = $seats->count();   // โซนนั่ง: ความจุ = จำนวนที่นั่งทั้งหมด
                foreach ($seats as $seat) {
                    $label = $seat->row.$seat->number;
                    if ($seat->seat_booking_status === 'LOCKED' && ! in_array($seat->seat_id, $ownLockedSeatIds, true)) {
                        $pending[] = $label;
                    } elseif (in_array($seat->seat_booking_status, ['LOCKED', 'RESERVED', 'SOLD'], true)) {
                        $occupied[] = $label;
                        $occupiedSeats[] = $label;
                    }
                }
            }

            return [
                'zone_id' => $zone->zone_id,
                'name' => $zone->name,
                'zone_type' => $zone->zone_type,
                'price'     => (int) $zone->price,
                'seats'     => $availableCount,
                'capacity'  => $capacity,      // ใช้คิดสีป้ายที่นั่งว่าง (<= 30% = เหลือง)
                'color'     => $zone->color,
                'rows'      => $rows,
                'cols'      => $cols,
                'occupied'  => $occupied,
                'pending'   => $pending,
            ];
        });

        return view('user.booking.booking', compact('concert', 'zones', 'occupiedSeats'));
    }

    /**
     * แสดงรายละเอียดคอนเสิร์ต ราคา และจำนวนบัตรที่ยังว่าง
     */
    public function detail(Request $request)
    {
        $concertController = new ConcertController;
        $concertId = (int) $request->query('concert_id');

        $concert = $concertId
            ? $concertController->getConcertDetail($concertId)
            : Concert::with(['organization', 'zones'])
                ->where('status', 'ON_SALE')
                ->where('is_deleted', 0)
                ->orderBy('event_datetime', 'asc')
                ->first();

        $zones = $concert
            ? $concert->zones->map(function ($zone) {
                return [
                    'name'     => $zone->name,
                    'price'    => (int) $zone->price,
                    'color'    => $zone->color,
                    'seats'    => $zone->getAvailableCapacity(),
                    'capacity' => $zone->zone_type === 'STANDING' ? (int) $zone->capacity : $zone->seats()->count(),
                ];
            })
            : collect();

        return view('user.booking.concert-detail', compact('concert', 'zones'));
    }

    /**
     * validateBookingSecurity(customerId, deviceFingerprint, captchaToken)
     * ตรวจความปลอดภัยก่อนสร้างการจอง (บัญชีถูกระงับ, CAPTCHA, device fingerprint)
     * param : customerId, deviceFingerprint, captchaToken
     * return : bool – true ถ้าอนุญาตให้จองได้
     */
    public function validateBookingSecurity(int $customerId, ?string $deviceFingerprint, ?string $captchaToken): bool
    {
        $user = AppUser::find($customerId);
        if (! $user || $user->isBanned()) {
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
        if (! $this->validateBookingSecurity($customerId, null, null)) {
            abort(403, 'บัญชีถูกระงับหรือพฤติกรรมไม่ได้รับอนุญาต');
        }

        $seatIds = array_values(array_unique(array_map('intval', $seatIds)));
        if (in_array(0, $seatIds, true)) {
            abort(422, 'รายการที่นั่งไม่ถูกต้อง');
        }

        $bookingCode = 'BKG-'.strtoupper(Str::random(10));
        if (! $this->seatLockService->acquireSeats($seatIds, $bookingCode)) {
            abort(409, 'ที่นั่งบางรายการกำลังถูกจองโดยผู้ใช้อื่น');
        }

        try {
            return DB::transaction(function () use ($customerId, $concertId, $seatIds, $standingItems, $bookingCode) {
                $concert = Concert::with('zones')->findOrFail($concertId);
                $totalAmount = 0;
                $standingData = [];

                // ── คำนวณยอดรวม SEATED ──
                $seatsData = [];
                foreach ($seatIds as $seatId) {
                    // lockForUpdate: กันผู้ใช้ 2 คนจองที่นั่งเดียวกันพร้อมกัน
                    $seat = Seat::with('zone')->lockForUpdate()->findOrFail($seatId);
                    if ($seat->seat_booking_status !== 'AVAILABLE') {
                        abort(409, "Seat {$seatId} ไม่ว่าง");
                    }
                    $totalAmount += (float) $seat->zone->price;
                    $seatsData[] = [
                        'seat' => $seat,
                        'zone' => $seat->zone,
                        'unit_price' => (float) $seat->zone->price,
                    ];
                }

                // ── คำนวณยอดรวม STANDING ──
                $standingQuantities = [];
                foreach ($standingItems as $item) {
                    if (! is_array($item)) {
                        abort(422, 'รายการบัตรยืนไม่ถูกต้อง');
                    }

                    $zoneId = (int) ($item['zone_id'] ?? 0);
                    $quantity = (int) ($item['quantity'] ?? 1);
                    if ($zoneId < 1 || $quantity < 1) {
                        abort(422, 'รายการบัตรยืนไม่ถูกต้อง');
                    }

                    $standingQuantities[$zoneId] = ($standingQuantities[$zoneId] ?? 0) + $quantity;
                }
                ksort($standingQuantities);

                foreach ($standingQuantities as $zoneId => $quantity) {
                    $zone = Zone::query()
                        ->where('zone_id', $zoneId)
                        ->where('concert_id', $concertId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($zone->zone_type !== 'STANDING') {
                        abort(422, 'รายการบัตรยืนไม่ถูกต้อง');
                    }

                    $reservedQuantity = BookingStandingItem::query()
                        ->where('zone_id', $zone->zone_id)
                        ->whereHas('booking', function ($query) {
                            $query->where('status', 'PENDING_PAYMENT')
                                ->where('expiry_time', '>', now());
                        })
                        ->sum('quantity');
                    $soldQuantity = (int) $zone->tickets()->sum('quantity');
                    $availableCapacity = max(0, (int) $zone->capacity - $soldQuantity - (int) $reservedQuantity);

                    if ($availableCapacity < $quantity) {
                        abort(409, "Zone {$zone->name} capacity ไม่เพียงพอ");
                    }

                    $totalAmount += (float) $zone->price * $quantity;
                    $standingData[] = [
                        'zone' => $zone,
                        'quantity' => $quantity,
                    ];
                }

                // ── สร้าง Booking ──
                $expiryTime = now()->addMinutes(Booking::SEAT_LOCK_MINUTES);
                $booking = Booking::create([
                    'booking_code' => $bookingCode,
                    'user_id' => $customerId,
                    'concert_id' => $concertId,
                    'booking_date' => now(),
                    'total_amount' => $totalAmount,
                    'status' => 'PENDING_PAYMENT',
                    'expiry_time' => $expiryTime,
                ]);

                // ── ล็อก Seat + บันทึก BookingSeat + SeatLockLog ──
                foreach ($seatsData as $seatData) {
                    $seatData['seat']->lockSeat();

                    BookingSeat::create([
                        'booking_id' => $booking->booking_id,
                        'seat_id' => $seatData['seat']->seat_id,
                        'zone_name_snapshot' => $seatData['zone']->name,
                        'seat_label_snapshot' => $seatData['seat']->row.$seatData['seat']->number,
                        'unit_price_snapshot' => $seatData['unit_price'],
                    ]);

                    SeatLockLog::create([
                        'booking_id' => $booking->booking_id,
                        'seat_id' => $seatData['seat']->seat_id,
                        'locked_at' => now(),
                        'expired_at' => $expiryTime,
                        'status' => 'LOCKED',
                    ]);
                }

                foreach ($standingData as $standingItem) {
                    BookingStandingItem::create([
                        'booking_id' => $booking->booking_id,
                        'zone_id' => $standingItem['zone']->zone_id,
                        'quantity' => $standingItem['quantity'],
                        'zone_name_snapshot' => $standingItem['zone']->name,
                        'unit_price_snapshot' => $standingItem['zone']->price,
                    ]);
                }

                return $booking;
            });
        } catch (Throwable $exception) {
            try {
                $this->seatLockService->releaseSeats($seatIds, $bookingCode);
            } catch (Throwable $releaseException) {
                report($releaseException);
            }

            throw $exception;
        }
    }

    /**
     * store(request)
     * สร้างการจองจากหน้า booking: โซนนั่งรับป้ายที่นั่ง (เช่น A1, A2) แปลงเป็น seat_id แล้วล็อกที่นั่ง
     * โซนยืนรับจำนวนบัตร (quantity) แล้วกันจำนวนไว้จนกว่าจะชำระเงินหรือหมดเวลา
     * param : request (concert_id, zone_id, seats[] หรือ quantity)
     * return : JsonResponse { booking_id, redirect } หรือข้อความผิดพลาด
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'concert_id' => ['required', 'integer'],
            'zone_id'    => ['required', 'integer'],
            'seats'      => ['nullable', 'array'],
            'seats.*'    => ['required', 'string', 'max:20'],
            'quantity'   => ['nullable', 'integer', 'min:1'],
        ]);

        $concert = \App\Models\Concert::where('concert_id', $data['concert_id'])
            ->where('is_deleted', 0)
            ->first();
        $zone = \App\Models\Zone::where('zone_id', $data['zone_id'])
            ->where('concert_id', $data['concert_id'])
            ->first();

        if (!$concert || !$zone) {
            return response()->json(['message' => 'ไม่พบคอนเสิร์ตหรือโซนที่เลือก'], 404);
        }

        // ปล่อยที่นั่งที่หมดเวลาก่อน เพื่อให้จองที่นั่งเดิมได้ทันทีที่ครบเวลา
        Booking::releaseExpired($concert->concert_id);

        // จองได้เฉพาะคอนเสิร์ตที่เปิดขายอยู่
        if ($concert->status !== 'ON_SALE') {
            return response()->json(['message' => 'คอนเสิร์ตนี้ยังไม่เปิดขายบัตร'], 422);
        }

        $maxTickets = (int) ($concert->max_ticket_per_order ?: 4);

        // ── โซนยืน: จองตามจำนวนบัตร ──
        if ($zone->zone_type === 'STANDING') {
            $quantity = (int) ($data['quantity'] ?? 0);
            if ($quantity < 1) {
                return response()->json(['message' => 'กรุณาเลือกจำนวนบัตร'], 422);
            }
            if ($quantity > $maxTickets) {
                return response()->json(['message' => "จองได้สูงสุด {$maxTickets} ใบต่อครั้ง"], 422);
            }

            $booking = $this->createBooking((int) $request->user()->user_id, $concert->concert_id, [], [
                ['zone_id' => $zone->zone_id, 'quantity' => $quantity],
            ]);

            return response()->json([
                'booking_id' => $booking->booking_id,
                'redirect'   => url('/payment?booking_id=' . $booking->booking_id),
            ], 201);
        }

        // ── โซนนั่ง: จองตามที่นั่งที่เลือก ──
        $labels = array_values(array_unique($data['seats'] ?? []));
        if (count($labels) === 0) {
            return response()->json(['message' => 'กรุณาเลือกที่นั่ง'], 422);
        }
        if (count($labels) > $maxTickets) {
            return response()->json(['message' => "จองได้สูงสุด {$maxTickets} ที่นั่งต่อครั้ง"], 422);
        }

        // แปลงป้ายที่นั่ง (แถว + เลข) เป็น seat_id ของโซนนี้
        $placeholders = implode(',', array_fill(0, count($labels), '?'));
        $seatIds = Seat::where('zone_id', $zone->zone_id)
            ->whereRaw("CONCAT(`row`, `number`) IN ({$placeholders})", $labels)
            ->pluck('seat_id')
            ->all();

        if (count($seatIds) !== count($labels)) {
            return response()->json(['message' => 'ไม่พบที่นั่งบางรายการในโซนนี้'], 422);
        }

        $booking = $this->createBooking((int) $request->user()->user_id, $concert->concert_id, $seatIds);

        return response()->json([
            'booking_id' => $booking->booking_id,
            'redirect'   => url('/payment?booking_id=' . $booking->booking_id),
        ], 201);
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
