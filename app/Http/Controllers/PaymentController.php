<?php

/**
 * PaymentController
 * จัดการการชำระเงิน: หน้าชำระเงิน ตรวจ rate limit สร้าง/ยืนยันการชำระเงิน และสร้างคำสั่งซื้อ
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 04/09/2569
 * Updated : 21/10/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Concert;
use App\Models\CustomerOrder;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /* ช่วงเวลาที่ใช้นับการชำระเงินล้มเหลว (นาที) */
    private const RATE_LIMIT_WINDOW_MINUTES = 15;

    /* จำนวนครั้งที่ชำระเงินล้มเหลวได้สูงสุดในช่วงเวลา */
    private const RATE_LIMIT_MAX_FAILURES = 5;

    /* ค่าธรรมเนียมบริการต่อคำสั่งซื้อ (บาท) */
    private const SERVICE_FEE = 30;

    /* ค่าประกันความคุ้มครอง (บาท) เมื่อเลือกประกันบัตรหรือประกันการเดินทาง */
    private const PROTECTION_FEE = 144;

    /* ตัวเลือกประกันที่รับได้ */
    private const PROTECTION_OPTIONS = ['show', 'travel', 'none'];

    /* รูปแบบหมายเลขคำสั่งซื้อ ORD-[รหัสคอนเสิร์ต]-[ปีเดือนวัน]-[ลำดับ 5 หลัก] */
    private const ORDER_CODE_PATTERN = '/^ORD-[A-Z0-9]+-\d{6}-\d{5}$/';

    /* แปลงช่องทางชำระเงินจากหน้าเว็บเป็นค่าที่เก็บในตาราง payment */
    private const METHOD_MAP = ['promptpay' => 'PROMPTPAY', 'credit' => 'CREDIT_CARD'];

    /**
     * index(request)
     * แสดงหน้าชำระเงิน ถ้ามี booking_id จะใช้ข้อมูลการจองจริง (ที่นั่ง ยอดเงิน เวลาที่เหลือ ผู้ซื้อ)
     * param : request (booking_id หรือ concert_id)
     * return : View booking.payment หรือ Redirect เมื่อการจองหมดเวลา
     */
    public function index(Request $request)
    {
        $bookingId = (int) $request->query('booking_id');

        if ($bookingId) {
            if (!$request->user()) {
                return redirect('/')->with('error', 'กรุณาเข้าสู่ระบบก่อนชำระเงิน');
            }

            $booking = $this->findOwnBooking($request, $bookingId);
            $booking->loadMissing(['bookingSeats', 'standingItems', 'concert.organization']);

            if ($booking->status !== 'PENDING_PAYMENT') {
                return redirect()->route('orders.history');
            }
            if ($booking->checkExpiry()) {
                $this->expireBooking($booking);
                return redirect('/booking?concert_id=' . $booking->concert_id)
                    ->with('error', 'หมดเวลาชำระเงิน กรุณาเลือกที่นั่งใหม่');
            }

            $concert = $booking->concert;
            $user = $request->user();
            $seats = $booking->bookingSeats;
            $standing = $booking->standingItems;
            $pendingPayment = $booking->payments()->where('status', 'PENDING')->latest('payment_id')->first();

            $checkout = [
                'bookingId'     => $booking->booking_id,
                'zoneName'      => $seats->pluck('zone_name_snapshot')
                    ->merge($standing->pluck('zone_name_snapshot'))
                    ->filter()->unique()->join(', '),
                // โซนยืนไม่มีเลขที่นั่ง แสดงเป็น "บัตรยืน"
                'seatNo'        => $seats->isNotEmpty() ? $seats->pluck('seat_label_snapshot')->filter()->join(', ') : 'บัตรยืน',
                'quantity'      => $seats->count() + (int) $standing->sum('quantity'),
                'ticketPrice'   => (float) ($seats->first()?->unit_price_snapshot ?? $standing->first()?->unit_price_snapshot ?? 0),
                'subtotal'      => (float) $booking->total_amount,
                'serviceFee'    => self::SERVICE_FEE,
                'protectionFee' => self::PROTECTION_FEE,
                'secondsLeft'   => max(0, (int) now()->diffInSeconds($booking->expiry_time, false)),
                'orderCode'     => $pendingPayment?->payment_metadata['orderCode'] ?? null,
                'paymentId'     => $pendingPayment?->payment_id,
                'buyer'         => [
                    'name'  => trim($user->first_name . ' ' . $user->last_name),
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
            ];

            return view('booking.payment', [
                'concert'      => $concert,
                'organization' => $concert?->organization,
                'checkout'     => $checkout,
            ]);
        }

        // ไม่มี booking_id: แสดงหน้าตัวอย่างแบบเดิม (ค่าจาก query string)
        $concertId = (int) $request->query('concert_id');

        $concert = $concertId
            ? Concert::with('organization')->where('concert_id', $concertId)->where('is_deleted', 0)->firstOrFail()
            : Concert::with('organization')->where('status', 'ON_SALE')->where('is_deleted', 0)->orderBy('event_datetime')->first();

        return view('booking.payment', [
            'concert'      => $concert,
            'organization' => $concert?->organization,
            'checkout'     => null,
        ]);
    }

    /**
     * store(request)
     * สร้างรายการชำระเงินของการจอง และออกหมายเลขคำสั่งซื้อไว้แสดงในหน้าต่าง QR
     * (ถ้ามีรายการชำระเงินที่รออยู่แล้ว จะใช้รายการเดิม หมายเลขคำสั่งซื้อจึงไม่เปลี่ยน)
     * param : request (booking_id, method, protection)
     * return : JsonResponse { payment_id, order_code, amount }
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'booking_id' => ['required', 'integer'],
            'method'     => ['required', 'in:' . implode(',', array_keys(self::METHOD_MAP))],
            'protection' => ['required', 'in:' . implode(',', self::PROTECTION_OPTIONS)],
        ]);

        $booking = $this->findOwnBooking($request, (int) $data['booking_id']);

        if ($booking->status !== 'PENDING_PAYMENT') {
            return response()->json(['message' => 'การจองนี้ชำระเงินแล้วหรือถูกยกเลิก'], 409);
        }
        if ($booking->checkExpiry()) {
            $this->expireBooking($booking);
            return response()->json(['message' => 'หมดเวลาชำระเงิน กรุณาเลือกที่นั่งใหม่'], 410);
        }

        $payment = $booking->payments()->where('status', 'PENDING')->latest('payment_id')->first();
        $method = self::METHOD_MAP[$data['method']];
        $amount = $this->calculateAmount($booking, $data['protection']);

        if ($payment) {
            // เปลี่ยนช่องทาง/ประกันได้ แต่หมายเลขคำสั่งซื้อเดิมยังคงอยู่
            $metadata = array_merge($payment->payment_metadata ?? [], [
                'protection'    => $data['protection'],
                'serviceFee'    => self::SERVICE_FEE,
                'protectionFee' => $data['protection'] === 'none' ? 0 : self::PROTECTION_FEE,
            ]);

            // รายการที่ออกเลขไว้ก่อนเปลี่ยนรูปแบบ (เช่น ORD-ZISQRXU9) ให้ออกเลขใหม่เป็น ORD-C0024-261021-00001
            if (!preg_match(self::ORDER_CODE_PATTERN, (string) ($metadata['orderCode'] ?? ''))) {
                $metadata['orderCode'] = \Illuminate\Support\Facades\DB::transaction(function () use ($booking) {
                    $concert = Concert::whereKey($booking->concert_id)->lockForUpdate()->firstOrFail();
                    return $this->generateOrderCode($concert);
                });
            }

            $payment->update([
                'method'           => $method,
                'amount'           => $amount,
                'payment_metadata' => $metadata,
            ]);
        } else {
            $request->merge(['method' => $method]);
            $payment = $this->processPayment($request, $booking->booking_id, $data['protection']);
        }

        return response()->json([
            'payment_id' => $payment->payment_id,
            'order_code' => $payment->payment_metadata['orderCode'] ?? null,
            'amount'     => (float) $payment->amount,
        ]);
    }

    /**
     * mockComplete(request, paymentId)
     * จำลองว่าธนาคารยืนยันการชำระเงินสำเร็จ (ใช้ได้เฉพาะเครื่อง dev ที่ APP_ENV=local แทน webhook ของ payment provider)
     * param : request, paymentId
     * return : JsonResponse { order_code, redirect }
     */
    public function mockComplete(Request $request, int $paymentId): JsonResponse
    {
        abort_unless(app()->environment('local'), 404);

        $payment = Payment::with('booking')->findOrFail($paymentId);
        abort_unless((int) $payment->booking?->user_id === (int) $request->user()->user_id, 403);

        if ($payment->status !== 'PENDING') {
            return response()->json(['message' => 'รายการนี้ไม่ได้อยู่ในสถานะรอชำระ'], 409);
        }

        $order = $this->completePayment($paymentId);

        return response()->json([
            'order_code' => $order->order_code,
            'redirect'   => route('orders.history'),
        ]);
    }

    /**
     * checkRateLimit(customerId, ipAddress, deviceFingerprint)
     * ตรวจว่าการชำระเงินล้มเหลวเกินจำนวนที่กำหนดในช่วงเวลาหรือไม่ (ตามผู้ใช้ IP และอุปกรณ์)
     * param : customerId, ipAddress, deviceFingerprint
     * return : bool – true ถ้ายังชำระเงินได้
     */
    public function checkRateLimit(int $customerId, string $ipAddress, ?string $deviceFingerprint): bool
    {
        $failCount = \App\Models\Payment::whereHas('booking', fn($q) => $q->where('user_id', $customerId))
            ->where('status', 'FAILED')
            ->where('created_at', '>=', now()->subMinutes(self::RATE_LIMIT_WINDOW_MINUTES))
            ->where(function ($q) use ($ipAddress, $deviceFingerprint) {
                $q->whereJsonContains('payment_metadata->ipAddress', $ipAddress);
                if ($deviceFingerprint) {
                    $q->orWhereJsonContains('payment_metadata->deviceFingerprint', $deviceFingerprint);
                }
            })
            ->count();

        return $failCount < self::RATE_LIMIT_MAX_FAILURES;
    }

    /**
     * processPayment(request, bookingId, protection)
     * ตรวจ rate limit แล้วสร้างรายการชำระเงิน (บันทึก IP, device fingerprint, ค่าธรรมเนียม และหมายเลขคำสั่งซื้อใน payment_metadata)
     * param : request, bookingId, protection
     * return : Payment ที่สร้างขึ้น
     */
    public function processPayment(Request $request, int $bookingId, string $protection = 'none'): \App\Models\Payment
    {
        $request->validate([
            'method' => ['required', 'in:PROMPTPAY,CREDIT_CARD'],
        ]);

        $booking = \App\Models\Booking::findOrFail($bookingId);
        $ipAddress = $request->ip();
        $deviceFingerprint = $request->header('X-Device-Fingerprint');

        if (!$this->checkRateLimit($booking->user_id, $ipAddress, $deviceFingerprint)) {
            abort(429, 'คำขอชำระเงินเกินจำนวนที่กำหนด กรุณารอสักครู่');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $booking, $bookingId, $protection, $ipAddress, $deviceFingerprint) {
            // ล็อกแถวคอนเสิร์ต กันสองรายการได้เลขลำดับคำสั่งซื้อซ้ำกัน
            $concert = Concert::whereKey($booking->concert_id)->lockForUpdate()->firstOrFail();

            return \App\Models\Payment::create([
                'booking_id'       => $bookingId,
                'amount'           => $this->calculateAmount($booking, $protection),
                'method'           => $request->input('method'),
                'status'           => 'PENDING',
                'payment_metadata' => [
                    'ipAddress'         => $ipAddress,
                    'deviceFingerprint' => $deviceFingerprint,
                    // ออกหมายเลขคำสั่งซื้อไว้ก่อน แสดงในหน้าต่าง QR และใช้ตอนสร้าง CustomerOrder
                    'orderCode'         => $this->generateOrderCode($concert),
                    'protection'        => $protection,
                    'serviceFee'        => self::SERVICE_FEE,
                    'protectionFee'     => $protection === 'none' ? 0 : self::PROTECTION_FEE,
                ],
                'created_at'       => now(),
            ]);
        });
    }

    /**
     * verifyPayment(paymentId)
     * ตรวจสอบสถานะธุรกรรมการชำระเงิน
     * param : paymentId
     * return : bool – true ถ้าชำระเงินสำเร็จ
     */
    public function verifyPayment(int $paymentId): bool
    {
        $payment = \App\Models\Payment::findOrFail($paymentId);
        // TODO: เรียก Payment Provider API จริง
        // ตัวอย่าง: ส่งคืน true ถ้า COMPLETED
        return $payment->status === 'COMPLETED';
    }

    /**
     * completePayment(paymentId)
     * ยืนยันการชำระเงินและการจอง สร้างคำสั่งซื้อพร้อม snapshot แล้วสร้างบัตร
     * param : paymentId
     * return : CustomerOrder ที่สร้างขึ้น
     */
    public function completePayment(int $paymentId): \App\Models\CustomerOrder
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($paymentId) {
            $payment = \App\Models\Payment::with('booking.concert.organization')->findOrFail($paymentId);
            $booking = $payment->booking;
            $concert = $booking->concert;
            $customer = \App\Models\AppUser::find($booking->user_id);

            // อัปเดต Payment → COMPLETED
            $payment->update(['status' => 'COMPLETED']);

            // ยืนยัน Booking
            $booking->confirmBooking();

            // สร้าง CustomerOrder พร้อม Snapshot
            $order = \App\Models\CustomerOrder::create([
                'booking_id'                   => $booking->booking_id,
                'payment_id'                   => $paymentId,
                'order_code'                   => $payment->payment_metadata['orderCode'] ?? $this->generateOrderCode($concert),
                'concert_name_snapshot'        => $concert->name,
                'artist_snapshot'              => $concert->artist,
                'venue_snapshot'               => $concert->venue,
                'event_datetime_snapshot'      => $concert->event_datetime,
                'organization_name_snapshot'   => $concert->organization?->name ?? '',
                'user_fiestname_snapshot'      => $customer?->first_name ?? '',
                'user_lastname_snapshot'       => $customer?->last_name ?? '',
                'user_phone_snapshot'          => $customer?->phone ?? '',
                'order_date'                   => now(),
            ]);

            // สร้าง Ticket ผ่าน TicketController
            (new TicketController())->generateTickets($order->order_id);

            return $order;
        });
    }

    /**
     * calculateAmount(booking, protection)
     * คำนวณยอดที่ต้องชำระ = ราคาบัตร + ค่าธรรมเนียมบริการ + ค่าประกัน (ถ้าเลือก)
     * param : booking, protection
     * return : float ยอดที่ต้องชำระ
     */
    private function calculateAmount(Booking $booking, string $protection): float
    {
        $protectionFee = $protection === 'none' ? 0 : self::PROTECTION_FEE;

        return (float) $booking->total_amount + self::SERVICE_FEE + $protectionFee;
    }

    /**
     * generateOrderCode(concert)
     * สร้างหมายเลขคำสั่งซื้อ ORD-[รหัสคอนเสิร์ต]-[ปีเดือนวัน]-[ลำดับ 5 หลัก] เช่น ORD-C0024-260901-00038
     * ลำดับนับต่อคอนเสิร์ตต่อวัน รวมเลขที่ออกไว้แล้วในรายการชำระเงินที่ยังรอชำระด้วย จึงไม่ซ้ำกัน
     * param : concert
     * return : string หมายเลขคำสั่งซื้อ
     */
    private function generateOrderCode(Concert $concert): string
    {
        $concertCode = $concert->concert_code ?: 'C' . str_pad((string) $concert->concert_id, 4, '0', STR_PAD_LEFT);
        $prefix = 'ORD-' . $concertCode . '-' . now()->format('ymd') . '-';
        $lastSeq = "CAST(SUBSTRING_INDEX(%s, '-', -1) AS UNSIGNED)";

        $maxOrderSeq = (int) CustomerOrder::where('order_code', 'like', $prefix . '%')
            ->selectRaw('MAX(' . sprintf($lastSeq, 'order_code') . ') AS seq')
            ->value('seq');

        $metaCode = "JSON_UNQUOTE(JSON_EXTRACT(payment_metadata, '$.orderCode'))";
        $maxPendingSeq = (int) Payment::whereRaw("{$metaCode} LIKE ?", [$prefix . '%'])
            ->selectRaw('MAX(' . sprintf($lastSeq, $metaCode) . ') AS seq')
            ->value('seq');

        $next = max($maxOrderSeq, $maxPendingSeq) + 1;

        return $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    /**
     * findOwnBooking(request, bookingId)
     * ดึงการจองที่เป็นของผู้ใช้ที่ล็อกอินอยู่ (ไม่ใช่ของตัวเองตอบ 404)
     * param : request, bookingId
     * return : Booking
     */
    private function findOwnBooking(Request $request, int $bookingId): Booking
    {
        return Booking::where('booking_id', $bookingId)
            ->where('user_id', $request->user()->user_id)
            ->firstOrFail();
    }

    /**
     * expireBooking(booking)
     * ยกเลิกการจองที่หมดเวลา และปลดล็อกที่นั่งกลับเป็น AVAILABLE
     * param : booking
     * return : void
     */
    private function expireBooking(Booking $booking): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($booking) {
            foreach ($booking->bookingSeats()->with('seat')->get() as $bookingSeat) {
                if ($bookingSeat->seat?->seat_booking_status === 'LOCKED') {
                    $bookingSeat->seat->unlockSeat();
                }
            }
            $booking->seatLockLogs()->where('status', 'LOCKED')->update([
                'status'      => 'EXPIRED',
                'released_at' => now(),
            ]);
            $booking->cancelBooking();
        });
    }
}
