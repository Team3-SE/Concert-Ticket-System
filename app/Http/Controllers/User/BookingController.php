<?php

/**
 * BookingController
 * จัดการการจองบัตรของลูกค้า: หน้าเลือกที่นั่ง ตรวจความปลอดภัย สร้างการจอง และประวัติการจอง
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 04/09/2569
 * Updated : 11/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Concert;
use App\Models\Seat;
use App\Models\SeatLockLog;
use App\Models\Zone;
use App\Models\ZoneInventory;
use App\Services\SeatLockService;
use Illuminate\Database\UniqueConstraintViolationException;
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
                ->whereDisplayStatus(Concert::STATUS_ON_SALE)   // คอนเสิร์ตที่จองได้จริงตอนนี้
                ->orderBy('event_datetime', 'asc')
                ->first();

        if (! $concert) {
            return view('user.booking.booking', [
                'concert' => null,
                'zones' => collect(),
                'occupiedSeats' => [],
            ]);
        }

        // ครบ 10 นาทีแล้วไม่ชำระ: ปล่อยที่นั่งกลับเป็นว่างก่อน (ก่อนตรวจว่าบัตรหมดหรือยัง และก่อนแสดงผังที่นั่ง)
        Booking::releaseExpired($concert->c_id);

        // จองไม่ได้ (ยังไม่เปิดขาย / เลยวันปิดขาย / การแสดงจบแล้ว / บัตรหมด / ยกเลิก) → กลับไปหน้ารายละเอียด
        if (! $concert->canBook()) {
            return redirect('/concert-detail?concert_id=' . $concert->c_id);
        }

        // ที่นั่งที่ผู้ใช้คนนี้ล็อกไว้เองและยังไม่ชำระ: ไม่นับเป็น "คนอื่นกำลังชำระเงิน" (สีส้ม) แต่ยังเลือกซ้ำไม่ได้
        $ownLockedSeatIds = $request->user()
            ? BookingSeat::whereHas('booking', function ($q) use ($request, $concert) {
                $q->where('b_user_id', $request->user()->u_id)
                    ->whereIn('b_zone_id', $concert->zones->pluck('z_id'))
                    ->where('status', Booking::STATUS_PENDING_PAYMENT);
            })->pluck('bs_seat_id')->map(fn ($id) => (int) $id)->all()
            : [];

        $occupiedSeats = [];
        $zones = $concert->zones->map(function ($zone) use (&$occupiedSeats, $ownLockedSeatIds) {
            $isStanding = $zone->isStanding();
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
                $seats = $zone->seats()->get(['s_id', 'row', 'number', 'seat_booking_status', 'seat_status']);
                $rows = $seats->pluck('row')->unique()->sort()->values()->all();
                $cols = $seats->pluck('number')->unique()->sort()->values()->all();
                $capacity = $seats->count();   // โซนนั่ง: ความจุ = จำนวนที่นั่งทั้งหมด
                foreach ($seats as $seat) {
                    $label = $seat->label();
                    if ($seat->seat_booking_status === Seat::STATUS_LOCKED && ! in_array((int) $seat->s_id, $ownLockedSeatIds, true)) {
                        $pending[] = $label;
                    } elseif (in_array($seat->seat_booking_status, [Seat::STATUS_LOCKED, Seat::STATUS_RESERVED, Seat::STATUS_SOLD], true)
                        || $seat->seat_status !== Seat::SEAT_ACTIVE) {
                        // ที่นั่งที่ถูกจอง/ขายแล้ว หรือที่นั่งที่ปิดใช้งาน (disabled / blocked) เลือกไม่ได้
                        $occupied[] = $label;
                        $occupiedSeats[] = $label;
                    }
                }
            }

            return [
                'zone_id' => $zone->z_id,   // ชื่อ key เดิมที่ JS หน้า booking ใช้ส่งกลับมาตอนจอง
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
                ->whereDisplayStatus(Concert::STATUS_ON_SALE)   // คอนเสิร์ตที่จองได้จริงตอนนี้
                ->orderBy('event_datetime', 'asc')
                ->first();

        // ปล่อยการจองที่หมดเวลาแล้วก่อนนับบัตรคงเหลือ (ไม่มี scheduler รันอยู่ก็ยังได้ตัวเลขที่ถูก)
        if ($concert) {
            Booking::releaseExpired($concert->c_id);
        }

        $zones = $concert
            ? $concert->zones->map(function ($zone) {
                return [
                    'name'     => $zone->name,
                    'price'    => (int) $zone->price,
                    'color'    => $zone->color,
                    'seats'    => $zone->getAvailableCapacity(),
                    'capacity' => $zone->isStanding() ? (int) $zone->capacity : $zone->seats()->count(),
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
     * สร้างการจอง 1 รายการต่อ 1 โซน (schema 3.4.2) พร้อม snapshot ชื่อโซนและราคาไว้ที่ booking
     * - โซนที่นั่ง: ล็อกที่นั่ง บันทึก BookingSeat และ SeatLockLog (UNIQUE active_seat_id กันล็อกซ้อนที่ระดับฐานข้อมูล)
     * - โซนยืน: ล็อกแถว zone_inventory ตรวจจำนวนคงเหลือ แล้วเพิ่ม held ตามจำนวนที่จอง
     * param : customerId, concertId, seatIds (โซนเดียวกันทั้งหมด), standingItems ([['zone_id' => ..., 'quantity' => ...]] ได้ 1 โซน)
     * return : Booking ที่สร้างขึ้น
     */
    public function createBooking(int $customerId, int $concertId, array $seatIds = [], array $standingItems = []): Booking
    {
        if (! $this->validateBookingSecurity($customerId, null, null)) {
            abort(403, 'บัญชีถูกระงับหรือพฤติกรรมไม่ได้รับอนุญาต');
        }

        // รวมจำนวนบัตรยืนตามโซน
        $standingQuantities = [];
        foreach ($standingItems as $item) {
            $zoneId = is_array($item) ? (int) ($item['zone_id'] ?? 0) : 0;
            $quantity = is_array($item) ? (int) ($item['quantity'] ?? 1) : 0;
            if ($zoneId < 1 || $quantity < 1) {
                abort(422, 'รายการบัตรยืนไม่ถูกต้อง');
            }
            $standingQuantities[$zoneId] = ($standingQuantities[$zoneId] ?? 0) + $quantity;
        }

        $seatIds = array_values(array_unique(array_map('intval', $seatIds)));
        if (($seatIds && $standingQuantities) || count($standingQuantities) > 1) {
            abort(422, 'จองได้ครั้งละ 1 โซนเท่านั้น');
        }
        if (! $seatIds && ! $standingQuantities) {
            abort(422, 'ไม่มีรายการที่ต้องการจอง');
        }

        try {
            return DB::transaction(function () use ($customerId, $concertId, $seatIds, $standingQuantities) {
                $inventory = null;
                $seats = collect();

                if ($seatIds) {
                    // lockForUpdate: กันผู้ใช้ 2 คนจองที่นั่งเดียวกันพร้อมกัน (เรียงตามรหัสเพื่อไม่ให้เกิด deadlock)
                    $seats = Seat::query()->whereIn('s_id', $seatIds)->orderBy('s_id')->lockForUpdate()->get();
                    if ($seats->count() !== count($seatIds)) {
                        abort(422, 'ไม่พบที่นั่งบางรายการ');
                    }
                    if ($seats->pluck('s_zone_id')->unique()->count() !== 1) {
                        abort(422, 'จองได้ครั้งละ 1 โซนเท่านั้น');
                    }

                    $zone = Zone::query()->whereKey($seats->first()->s_zone_id)->where('z_concert_id', $concertId)->firstOrFail();
                    foreach ($seats as $seat) {
                        if ($seat->seat_booking_status !== Seat::STATUS_AVAILABLE || $seat->seat_status !== Seat::SEAT_ACTIVE) {
                            abort(409, "ที่นั่ง {$seat->label()} ไม่ว่างแล้ว กรุณาเลือกที่นั่งใหม่");
                        }
                    }
                    $quantity = $seats->count();
                    $standingQuantity = null;
                } else {
                    $zoneId = (int) array_key_first($standingQuantities);
                    $quantity = $standingQuantities[$zoneId];
                    $zone = Zone::query()->whereKey($zoneId)->where('z_concert_id', $concertId)->firstOrFail();
                    if (! $zone->isStanding()) {
                        abort(422, 'รายการบัตรยืนไม่ถูกต้อง');
                    }

                    $inventory = ZoneInventory::lockForZone((int) $zone->z_id);
                    if ($inventory->available((int) $zone->capacity) < $quantity) {
                        abort(409, "โซน {$zone->name} เหลือบัตรไม่พอ");
                    }
                    $standingQuantity = $quantity;
                }

                $unitPrice = (float) $zone->price;

                // ── สร้าง Booking (1 การจอง = 1 โซน) ──
                $booking = Booking::create([
                    'booking_code'        => 'BKG-'.strtoupper(Str::random(10)),
                    'b_user_id'           => $customerId,
                    'b_zone_id'           => $zone->z_id,
                    'booking_date'        => now(),
                    'total_amount'        => $unitPrice * $quantity,
                    'status'              => Booking::STATUS_PENDING_PAYMENT,
                    'expiry_time'         => now()->addMinutes(Booking::SEAT_LOCK_MINUTES),
                    'zone_price_snapshot' => $unitPrice,
                    'zone_name_snapshot'  => $zone->name,
                    'standing_quantity'   => $standingQuantity,
                ]);

                // ── โซนที่นั่ง: ล็อก Seat + บันทึก BookingSeat + SeatLockLog ──
                foreach ($seats as $seat) {
                    $seat->lockSeat();

                    BookingSeat::create([
                        'bs_booking_id'       => $booking->b_id,
                        'bs_seat_id'          => $seat->s_id,
                        'seat_label_snapshot' => $seat->label(),
                    ]);

                    SeatLockLog::create([
                        'sll_booking_id' => $booking->b_id,
                        'sll_seat_id'    => $seat->s_id,
                        'locked_at'      => now(),
                        'expires_at'     => now()->addMinutes(Booking::SEAT_LOCK_MINUTES),
                    ]);
                }

                // ── โซนยืน: กันจำนวนบัตรไว้จนกว่าจะชำระเงินหรือหมดเวลา ──
                $inventory?->hold($quantity);

                return $booking;
            });
        } catch (UniqueConstraintViolationException $e) {
            // ที่นั่งมีล็อกที่ยังมีผลอยู่แล้ว (seat_lock_log.active_seat_id ซ้ำ) = มีคนจองที่นั่งนี้ไปพร้อมกัน
            abort(409, 'ที่นั่งบางที่ถูกจองไปแล้ว กรุณาเลือกที่นั่งใหม่');
        }
    }

    /**
     * store(request)
     * สร้างการจองจากหน้า booking: โซนนั่งรับป้ายที่นั่ง (เช่น A1, A2) แปลงเป็นรหัสที่นั่ง (s_id) แล้วล็อกที่นั่ง
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

        // Concert ใช้ SoftDeletes: คอนเสิร์ตที่ถูกลบจะหาไม่เจอเอง
        $concert = Concert::find($data['concert_id']);
        $zone = $concert
            ? Zone::query()->whereKey($data['zone_id'])->where('z_concert_id', $concert->c_id)->first()
            : null;

        if (! $concert || ! $zone) {
            return response()->json(['message' => 'ไม่พบคอนเสิร์ตหรือโซนที่เลือก'], 404);
        }

        // ปล่อยที่นั่งที่หมดเวลาก่อน เพื่อให้จองที่นั่งเดิมได้ทันทีที่ครบเวลา
        Booking::releaseExpired($concert->c_id);

        // จองได้เฉพาะคอนเสิร์ตที่เปิดขายอยู่ ยังอยู่ในช่วงขายบัตร ยังไม่ถึงเวลาแสดง และบัตรยังไม่หมด
        if (! $concert->canBook()) {
            return response()->json(['message' => $concert->bookingBlockedMessage()], 422);
        }

        $maxTickets = (int) ($concert->max_ticket_per_order ?: 4);
        $customerId = (int) $request->user()->u_id;

        // ── โซนยืน: จองตามจำนวนบัตร ──
        if ($zone->isStanding()) {
            $quantity = (int) ($data['quantity'] ?? 0);
            if ($quantity < 1) {
                return response()->json(['message' => 'กรุณาเลือกจำนวนบัตร'], 422);
            }
            if ($quantity > $maxTickets) {
                return response()->json(['message' => "จองได้สูงสุด {$maxTickets} ใบต่อครั้ง"], 422);
            }

            $booking = $this->createBooking($customerId, (int) $concert->c_id, [], [
                ['zone_id' => $zone->z_id, 'quantity' => $quantity],
            ]);

            return response()->json([
                'booking_id' => $booking->b_id,
                'redirect'   => url('/payment?booking_id=' . $booking->b_id),
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

        // แปลงป้ายที่นั่ง (แถว + เลข) เป็นรหัสที่นั่งของโซนนี้
        $placeholders = implode(',', array_fill(0, count($labels), '?'));
        $seatIds = Seat::where('s_zone_id', $zone->z_id)
            ->whereRaw("CONCAT(`row`, `number`) IN ({$placeholders})", $labels)
            ->pluck('s_id')
            ->all();

        if (count($seatIds) !== count($labels)) {
            return response()->json(['message' => 'ไม่พบที่นั่งบางรายการในโซนนี้'], 422);
        }

        $booking = $this->createBooking($customerId, (int) $concert->c_id, $seatIds);

        return response()->json([
            'booking_id' => $booking->b_id,
            'redirect'   => url('/payment?booking_id=' . $booking->b_id),
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
            ->where('b_user_id', $customerId)
            ->orderBy('booking_date', 'desc')
            ->get();
    }
}
