<?php

/**
 * PaymentController
 * จัดการการชำระเงิน: หน้าชำระเงิน ตรวจ rate limit สร้าง/ยืนยันการชำระเงิน และสร้างคำสั่งซื้อ
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 04/09/2569
 * Updated : 11/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\Booking;
use App\Models\Concert;
use App\Models\CustomerOrder;
use App\Models\Payment;
use App\Services\PaymockService;
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

    /* ค่าประกันต่อ 1 ตัวเลือก = 5% ของราคาบัตรรวม (เลือกได้ทั้งสองอย่าง คิดค่าประกันแยกกันแล้วรวมกัน) */
    private const PROTECTION_RATE = 0.05;

    /* ตัวเลือกประกันที่รับได้ (ไม่เลือกเลยก็ได้) */
    private const PROTECTION_OPTIONS = ['show', 'travel'];

    /* รูปแบบหมายเลขคำสั่งซื้อตาม Public Code Format Specification V2.0.0: ORD-[A-Z][0-9]{6}-[0-9]{6} */
    private const ORDER_CODE_PATTERN = '/^ORD-[A-Z][0-9]{6}-[0-9]{6}$/';

    /* ลำดับสูงสุดของรหัสต่อคอนเสิร์ต (6 หลัก) */
    private const MAX_CODE_SEQUENCE = 999999;

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
            $booking->loadMissing(['bookingSeats', 'zone', 'concert.organization']);

            if ($booking->status !== Booking::STATUS_PENDING_PAYMENT) {
                return redirect()->route('tickets.index');
            }
            if ($booking->checkExpiry()) {
                $this->expireBooking($booking);
                return redirect('/booking?concert_id=' . $booking->concertId())
                    ->with('error', 'หมดเวลาชำระเงิน กรุณาเลือกที่นั่งใหม่');
            }

            $concert = $booking->concert;
            $user = $request->user();
            $seats = $booking->bookingSeats;
            $pendingPayment = $booking->payments()->where('status', Payment::STATUS_PENDING)->latest('p_id')->first();

            // schema 3.4.2: 1 การจอง = 1 โซน ชื่อโซนและราคาต่อใบเก็บไว้ที่ booking
            $checkout = [
                'bookingId'     => $booking->b_id,
                'zoneName'      => (string) $booking->zone_name_snapshot,
                // โซนยืนไม่มีเลขที่นั่ง แสดงเป็น "บัตรยืน"
                'seatNo'        => $seats->isNotEmpty() ? $seats->pluck('seat_label_snapshot')->filter()->join(', ') : 'บัตรยืน',
                'quantity'      => $booking->isStanding() ? (int) $booking->standing_quantity : $seats->count(),
                'ticketPrice'   => (float) $booking->zone_price_snapshot,
                'subtotal'      => (float) $booking->total_amount,
                'serviceFee'    => self::SERVICE_FEE,
                'protectionRate' => self::PROTECTION_RATE,
                'secondsLeft'   => max(0, (int) now()->diffInSeconds($booking->expiry_time, false)),
                'orderCode'     => $pendingPayment?->payment_metadata['orderCode'] ?? null,
                'paymentId'     => $pendingPayment?->p_id,
                'gatewayEnabled' => app(PaymockService::class)->enabled(),   // true = ชำระผ่าน PayMock (รองรับบัตรเครดิต)
                'buyer'         => [
                    'name'  => trim($user->first_name . ' ' . $user->last_name),
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
            ];

            return view('user.booking.payment', [
                'concert'      => $concert,
                'organization' => $concert?->organization,
                'checkout'     => $checkout,
            ]);
        }

        // ไม่มี booking_id: แสดงหน้าตัวอย่างแบบเดิม (ค่าจาก query string)
        $concertId = (int) $request->query('concert_id');

        $concert = $concertId
            ? Concert::with('organization')->whereKey($concertId)->firstOrFail()
            : Concert::with('organization')->where('status', Concert::STATUS_ON_SALE)->orderBy('event_datetime')->first();

        return view('user.booking.payment', [
            'concert'      => $concert,
            'organization' => $concert?->organization,
            'checkout'     => null,
        ]);
    }

    /**
     * store(request)
     * สร้างรายการชำระเงินของการจอง และออกหมายเลขคำสั่งซื้อไว้แสดงในหน้าต่าง QR
     * (ถ้ามีรายการชำระเงินที่รออยู่แล้ว จะใช้รายการเดิม หมายเลขคำสั่งซื้อจึงไม่เปลี่ยน)
     * param : request (booking_id, method, protection = array ของ show/travel จะว่างก็ได้)
     * return : JsonResponse { payment_id, order_code, amount }
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'booking_id' => ['required', 'integer'],
            'method'     => ['required', 'in:' . implode(',', array_keys(self::METHOD_MAP))],
            'protection'   => ['nullable', 'array', 'max:' . count(self::PROTECTION_OPTIONS)],
            'protection.*' => ['string', 'distinct', 'in:' . implode(',', self::PROTECTION_OPTIONS)],
        ]);
        $protection = array_values($data['protection'] ?? []);

        $booking = $this->findOwnBooking($request, (int) $data['booking_id']);

        if ($booking->status !== Booking::STATUS_PENDING_PAYMENT) {
            return response()->json(['message' => 'การจองนี้ชำระเงินแล้วหรือถูกยกเลิก'], 409);
        }
        if ($booking->checkExpiry()) {
            $this->expireBooking($booking);
            return response()->json(['message' => 'หมดเวลาชำระเงิน กรุณาเลือกที่นั่งใหม่'], 410);
        }

        $payment = $booking->payments()->where('status', Payment::STATUS_PENDING)->latest('p_id')->first();
        $method = self::METHOD_MAP[$data['method']];
        $amount = $this->calculateAmount($booking, $protection);

        if ($payment) {
            // เปลี่ยนช่องทาง/ประกันได้ แต่หมายเลขคำสั่งซื้อเดิมยังคงอยู่
            $metadata = array_merge($payment->payment_metadata ?? [], [
                'protection'    => $protection,
                'serviceFee'    => self::SERVICE_FEE,
                'protectionFee' => $this->calculateProtectionFee($booking, $protection),
            ]);

            // รายการที่ออกเลขไว้ก่อนเปลี่ยนรูปแบบ (เช่น ORD-ZISQRXU9, ORD-C0024-261021-00001) ให้ออกเลขใหม่เป็น ORD-C000024-000001
            if (!preg_match(self::ORDER_CODE_PATTERN, (string) ($metadata['orderCode'] ?? ''))) {
                $metadata['orderCode'] = \Illuminate\Support\Facades\DB::transaction(function () use ($booking) {
                    $concert = Concert::whereKey($booking->concertId())->lockForUpdate()->firstOrFail();
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
            $payment = $this->processPayment($request, $booking->b_id, $protection);
        }

        // เชื่อม PayMock: สร้างรายการที่ gateway (ได้ QR / ลิงก์กรอกบัตร) ถ้าไม่ได้ตั้งค่า PAYMOCK_URL จะใช้การจำลองแบบเดิม
        $gateway = null;
        $paymock = app(PaymockService::class);
        if ($paymock->enabled()) {
            try {
                $gateway = $this->ensureGatewayPayment($paymock, $payment, $booking);
            } catch (\Throwable $e) {
                report($e);
                return response()->json(['message' => 'ระบบชำระเงินไม่พร้อมใช้งานชั่วคราว กรุณาลองใหม่อีกครั้ง'], 502);
            }
        }

        return response()->json([
            'payment_id' => $payment->p_id,   // ชื่อ key เดิมที่ JS หน้า payment ใช้
            'order_code' => $payment->payment_metadata['orderCode'] ?? null,
            'amount'     => (float) $payment->amount,
            'gateway'    => $gateway,         // null = ไม่ใช้ gateway | { method, qrcode_url, checkout_url }
        ]);
    }

    /**
     * ensureGatewayPayment(paymock, payment, booking)
     * ให้ payment มีรายการที่ PayMock ตรงกับช่องทางและยอดปัจจุบัน (ใช้รายการเดิมถ้ายังตรง ไม่ตรงก็ยกเลิกแล้วสร้างใหม่)
     * param : paymock, payment (PENDING), booking
     * return : array { method, qr_image, qrcode_url, checkout_url, bank_simulator_url }
     */
    private function ensureGatewayPayment(PaymockService $paymock, Payment $payment, Booking $booking): array
    {
        $payment->refresh();
        $meta = $payment->payment_metadata ?? [];
        $gateway = $meta['gateway'] ?? null;

        $reusable = $payment->provider_transaction_id
            && $gateway
            && ($gateway['method'] ?? null) === $payment->method
            && abs((float) ($gateway['amount'] ?? 0) - (float) $payment->amount) < 0.01;

        if (!$reusable) {
            if ($payment->provider_transaction_id) {
                $paymock->cancelPayment($payment->provider_transaction_id);
            }

            $created = $paymock->createPayment($payment, $booking, ((int) ($gateway['attempt'] ?? 0)) + 1);
            $gateway = $created + ['method' => $payment->method, 'amount' => (float) $payment->amount];

            $payment->update([
                'provider_transaction_id' => $created['id'],
                'payment_metadata'        => array_merge($meta, ['gateway' => $gateway]),
            ]);
        }

        return [
            'method'       => $payment->method,
            'qr_image'     => $payment->method === 'PROMPTPAY' ? $paymock->fetchQrImage($payment->provider_transaction_id) : null,   // ภาพ QR จาก PayMock (data URI)
            'qrcode_url'   => $paymock->publicUrl($gateway['qrcode_url'] ?? null),   // สำรอง: ให้เบราว์เซอร์โหลดจาก PayMock ตรง
            'checkout_url' => $paymock->publicUrl($gateway['checkout_url'] ?? null),
            // ลิงก์จำลองหน้าแอปธนาคาร (เฉพาะเครื่อง dev ใช้แทนการสแกน QR จริง)
            'bank_simulator_url' => app()->environment('local') ? $paymock->publicUrl($gateway['bank_simulator_url'] ?? null) : null,
        ];
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
        abort_unless((int) $payment->booking?->b_user_id === (int) $request->user()->u_id, 403);

        if ($payment->status !== Payment::STATUS_PENDING) {
            return response()->json(['message' => 'รายการนี้ไม่ได้อยู่ในสถานะรอชำระ'], 409);
        }

        $order = $this->completePayment($paymentId);

        return response()->json([
            'order_code' => $order->order_code,
            'redirect'   => route('tickets.index'),
        ]);
    }

    /**
     * gatewayReturn(request, bookingId)
     * จุดกลับจาก Hosted Checkout ของบัตรเครดิต Redirect ไม่ใช่หลักฐานการชำระเงิน:
     * อ่านสถานะล่าสุดจาก gateway อีกครั้งก่อนเลือกหน้าปลายทางเสมอ เพื่อรองรับ webhook
     * ที่ยังรอ queue อยู่หรือผู้ใช้ปิดหน้า checkout ก่อน webhook มาถึง.
     */
    public function gatewayReturn(Request $request, int $bookingId): \Illuminate\Http\RedirectResponse
    {
        $booking = $this->findOwnBooking($request, $bookingId);
        $payment = $booking->payments()->latest('p_id')->first();

        if ($payment
            && $payment->method === 'CREDIT_CARD'
            && $payment->status === Payment::STATUS_PENDING
            && $payment->provider_transaction_id
            && app(PaymockService::class)->enabled()) {
            // ไม่อ่านผลใด ๆ จาก redirect; GET นี้ใช้ API key ระหว่าง backend กับ gateway.
            app(PaymockService::class)->syncPayment($payment, 'return');
            $booking->refresh();
            $payment->refresh();
        }

        if ($booking->status === Booking::STATUS_CONFIRMED) {
            return redirect()->route('tickets.index');
        }

        if ($booking->checkExpiry()) {
            $this->expireBooking($booking);

            return redirect('/booking?concert_id=' . $booking->concertId())
                ->with('error', 'หมดเวลาชำระเงิน กรุณาเลือกที่นั่งใหม่');
        }

        $message = $payment?->status === Payment::STATUS_FAILED
            ? 'การชำระเงินไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'
            : 'กำลังยืนยันผลการชำระเงิน กรุณาตรวจสอบสถานะอีกครั้ง';

        return redirect('/payment?booking_id=' . $booking->b_id)->with('error', $message);
    }

    /**
     * checkRateLimit(customerId, ipAddress, deviceFingerprint)
     * ตรวจว่าการชำระเงินล้มเหลวเกินจำนวนที่กำหนดในช่วงเวลาหรือไม่ (ตามผู้ใช้ IP และอุปกรณ์)
     * param : customerId, ipAddress, deviceFingerprint
     * return : bool – true ถ้ายังชำระเงินได้
     */
    public function checkRateLimit(int $customerId, string $ipAddress, ?string $deviceFingerprint): bool
    {
        $failCount = Payment::whereHas('booking', fn ($q) => $q->where('b_user_id', $customerId))
            ->where('status', Payment::STATUS_FAILED)
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
     * param : request, bookingId, protection (array ของ show/travel)
     * return : Payment ที่สร้างขึ้น
     */
    public function processPayment(Request $request, int $bookingId, array $protection = []): \App\Models\Payment
    {
        $request->validate([
            'method' => ['required', 'in:PROMPTPAY,CREDIT_CARD'],
        ]);

        $booking = \App\Models\Booking::findOrFail($bookingId);
        $ipAddress = $request->ip();
        $deviceFingerprint = $request->header('X-Device-Fingerprint');

        if (!$this->checkRateLimit((int) $booking->b_user_id, $ipAddress, $deviceFingerprint)) {
            abort(429, 'คำขอชำระเงินเกินจำนวนที่กำหนด กรุณารอสักครู่');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $booking, $bookingId, $protection, $ipAddress, $deviceFingerprint) {
            // ล็อกแถวคอนเสิร์ต กันสองรายการได้เลขลำดับคำสั่งซื้อซ้ำกัน
            $concert = Concert::whereKey($booking->concertId())->lockForUpdate()->firstOrFail();

            return Payment::create([
                'p_booking_id'     => $bookingId,
                'amount'           => $this->calculateAmount($booking, $protection),
                'method'           => $request->input('method'),
                'status'           => Payment::STATUS_PENDING,
                'payment_metadata' => [
                    'ipAddress'         => $ipAddress,
                    'deviceFingerprint' => $deviceFingerprint,
                    // ออกหมายเลขคำสั่งซื้อไว้ก่อน แสดงในหน้าต่าง QR และใช้ตอนสร้าง CustomerOrder
                    'orderCode'         => $this->generateOrderCode($concert),
                    'protection'        => $protection,
                    'serviceFee'        => self::SERVICE_FEE,
                    'protectionFee'     => $this->calculateProtectionFee($booking, $protection),
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
        return $payment->status === Payment::STATUS_COMPLETED;
    }

    /**
     * completePayment(paymentId)
     * ยืนยันการชำระเงินและการจอง สร้างคำสั่งซื้อพร้อม snapshot แล้วสร้างบัตร
     * param : paymentId
     * return : CustomerOrder ที่สร้างขึ้น
     */
    public function completePayment(int $paymentId): \App\Models\CustomerOrder
    {
        $order = \Illuminate\Support\Facades\DB::transaction(function () use ($paymentId) {
            $payment = Payment::with(['booking.zone', 'booking.concert.organization'])->findOrFail($paymentId);
            $booking = $payment->booking;

            // การจองหมดเวลา/ถูกยกเลิกแล้ว (ที่นั่งถูกปล่อยไปแล้ว) ห้ามยืนยันการชำระเงิน
            if ($booking->status !== Booking::STATUS_PENDING_PAYMENT) {
                abort(409, 'การจองนี้หมดเวลาหรือถูกยกเลิกแล้ว');
            }

            $concert = $booking->concert;
            $customer = AppUser::find($booking->b_user_id);

            // อัปเดต Payment → COMPLETED
            $payment->update(['status' => Payment::STATUS_COMPLETED]);

            // ยืนยัน Booking (ที่นั่งเป็น sold, ปิดล็อกที่นั่ง, โซนยืนย้ายจาก held ไป sold)
            $booking->confirmBooking();

            // สร้าง CustomerOrder พร้อม Snapshot (schema 3.4.2 เก็บโซน ราคา ยอดรวม จำนวน และอีเมลผู้ซื้อด้วย)
            $order = CustomerOrder::create([
                'uo_booking_id'              => $booking->b_id,
                'uo_payment_id'              => $payment->p_id,
                'order_code'                 => $payment->payment_metadata['orderCode'] ?? $this->generateOrderCode($concert),
                'concert_name_snapshot'      => $concert->name,
                'artist_snapshot'            => $concert->artist,
                'venue_snapshot'             => $concert->venue,
                'event_datetime_snapshot'    => $concert->event_datetime,
                'organization_name_snapshot' => $concert->organization?->name ?? '',
                'user_firstname_snapshot'    => $customer?->first_name ?? '',
                'user_lastname_snapshot'     => $customer?->last_name ?? '',
                'user_phone_snapshot'        => $customer?->phone,
                'user_email_snapshot'        => $customer?->email,
                'zone_name_snapshot'         => $booking->zone_name_snapshot,
                'unit_price_snapshot'        => $booking->zone_price_snapshot,
                'total_amount_snapshot'      => $booking->total_amount,
                'quantity'                   => $booking->ticketQuantity(),
                'order_date'                 => now(),
            ]);

            // สร้าง Ticket ผ่าน TicketController (1 ใบต่อ 1 ที่นั่ง/บัตรยืน)
            (new TicketController())->generateTickets($order->uo_id);

            return $order;
        });

        // ปล่อยล็อกที่นั่งใน Redis หลังบันทึกฐานข้อมูลสำเร็จ (ที่นั่งขายแล้ว ไม่ต้องล็อกต่อ)
        Booking::find($order->uo_booking_id)?->releaseRedisSeatLocks();

        return $order;
    }

    /**
     * status(request, bookingId)
     * ตรวจสถานะการจองกับ server (หน้าชำระเงินเรียกตอนตัวนับเวลาถึง 0 ก่อนแจ้งว่าไม่สำเร็จ)
     * param : request, bookingId
     * return : JsonResponse { status, seconds_left }
     */
    public function status(Request $request, int $bookingId): JsonResponse
    {
        $booking = $this->findOwnBooking($request, $bookingId);
        $payment = $booking->payments()->latest('p_id')->first();

        // webhook ยังไม่มา/หาย: ถาม PayMock ตรง ๆ แล้วปรับสถานะ (หน้าชำระเงินเรียกทุกไม่กี่วินาทีขณะเปิด QR)
        if ($payment && $booking->status === Booking::STATUS_PENDING_PAYMENT
            && $payment->status === Payment::STATUS_PENDING && $payment->provider_transaction_id) {
            app(PaymockService::class)->syncPayment($payment, 'poll');
            $booking->refresh();
            $payment->refresh();
        }

        return response()->json([
            'status'         => $booking->status,
            'payment_status' => $payment?->status,   // PENDING | COMPLETED | FAILED (FAILED = ชำระไม่สำเร็จ ลองใหม่ได้ถ้ายังไม่หมดเวลา)
            'seconds_left'   => $booking->expiry_time ? max(0, (int) now()->diffInSeconds($booking->expiry_time, false)) : 0,
        ]);
    }

    /**
     * cancel(request, bookingId)
     * ยกเลิกการจองที่ยังรอชำระเงิน (กดย้อนกลับจากหน้าชำระเงิน) แล้วปลดล็อกที่นั่งกลับเป็น AVAILABLE
     * ไม่ยกเลิกถ้าการจองไม่ได้อยู่ในสถานะรอชำระ หรือมีรายการชำระเงินที่รอยืนยันอยู่ (เปิด QR แล้ว)
     * param : request, bookingId
     * return : JsonResponse { cancelled }
     */
    public function cancel(Request $request, int $bookingId): JsonResponse
    {
        $booking = $this->findOwnBooking($request, $bookingId);

        if ($booking->status !== Booking::STATUS_PENDING_PAYMENT) {
            return response()->json(['cancelled' => false, 'message' => 'การจองนี้ไม่ได้อยู่ในสถานะรอชำระเงิน'], 409);
        }

        if ($booking->payments()->where('status', Payment::STATUS_PENDING)->exists()) {
            return response()->json(['cancelled' => false, 'message' => 'มีรายการชำระเงินที่รอยืนยันอยู่'], 409);
        }

        $this->expireBooking($booking);

        return response()->json(['cancelled' => true]);
    }

    /**
     * calculateProtectionFee(booking, protection)
     * คำนวณค่าประกัน = 5% ของราคาบัตรรวม ต่อประกัน 1 ตัวเลือกที่เลือก (เลือกทั้งสองอย่างจะคิดสองเท่า)
     * param : booking, protection (array ของ show/travel)
     * return : float ค่าประกันรวม
     */
    private function calculateProtectionFee(Booking $booking, array $protection): float
    {
        $perOption = round((float) $booking->total_amount * self::PROTECTION_RATE, 2);

        return $perOption * count($protection);
    }

    /**
     * calculateAmount(booking, protection)
     * คำนวณยอดที่ต้องชำระ = ราคาบัตร + ค่าธรรมเนียมบริการ + ค่าประกัน (ถ้าเลือก)
     * param : booking, protection (array ของ show/travel)
     * return : float ยอดที่ต้องชำระ
     */
    private function calculateAmount(Booking $booking, array $protection): float
    {
        return (float) $booking->total_amount + self::SERVICE_FEE + $this->calculateProtectionFee($booking, $protection);
    }

    /**
     * generateOrderCode(concert)
     * สร้างหมายเลขคำสั่งซื้อ ORD-[รหัสคอนเสิร์ต 7 ตัว]-[ลำดับ 6 หลัก] เช่น ORD-C000024-000038 (Public Code Format Specification V2.0.0)
     * ลำดับนับต่อคอนเสิร์ต ไม่ผูกกับวันที่ รวมเลขที่ออกไว้แล้วในรายการชำระเงินทุกรายการ จึงไม่ออกเลขซ้ำกับที่เคยแสดงให้ผู้ใช้เห็น
     * ต้องเรียกระหว่างที่ล็อกแถวคอนเสิร์ต (lockForUpdate ใน transaction) เพื่อให้ออกเลขแบบ atomic
     * param : concert
     * return : string หมายเลขคำสั่งซื้อ
     */
    private function generateOrderCode(Concert $concert): string
    {
        $prefix = 'ORD-' . $concert->publicCode() . '-';
        $pattern = '/^' . preg_quote($prefix, '/') . '([0-9]{6})$/';

        $orderCodes = CustomerOrder::where('order_code', 'like', $prefix . '%')->pluck('order_code');

        $metaCode = "JSON_UNQUOTE(JSON_EXTRACT(payment_metadata, '$.orderCode'))";
        $issuedCodes = Payment::whereRaw("{$metaCode} LIKE ?", [$prefix . '%'])
            ->selectRaw("{$metaCode} AS order_code")
            ->pluck('order_code');

        $lastSequence = 0;
        foreach ($orderCodes->merge($issuedCodes) as $code) {
            if (preg_match($pattern, (string) $code, $matches)) {
                $lastSequence = max($lastSequence, (int) $matches[1]);
            }
        }

        $next = $lastSequence + 1;
        if ($next > self::MAX_CODE_SEQUENCE) {
            throw new \RuntimeException("Order sequence is exhausted for concert {$concert->publicCode()}.");
        }

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    /**
     * findOwnBooking(request, bookingId)
     * ดึงการจองที่เป็นของผู้ใช้ที่ล็อกอินอยู่ (ไม่ใช่ของตัวเองตอบ 404)
     * param : request, bookingId
     * return : Booking
     */
    private function findOwnBooking(Request $request, int $bookingId): Booking
    {
        return Booking::whereKey($bookingId)
            ->where('b_user_id', $request->user()->u_id)
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
        $booking->releaseSeats();
    }
}
