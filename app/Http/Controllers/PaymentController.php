<?php

/**
 * PaymentController
 * จัดการการชำระเงิน: หน้าชำระเงิน ตรวจ rate limit สร้าง/ยืนยันการชำระเงิน และสร้างคำสั่งซื้อ
 * Author :
 * Created :
 */

namespace App\Http\Controllers;

use App\Models\Concert;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /* ช่วงเวลาที่ใช้นับการชำระเงินล้มเหลว (นาที) */
    private const RATE_LIMIT_WINDOW_MINUTES = 15;

    /* จำนวนครั้งที่ชำระเงินล้มเหลวได้สูงสุดในช่วงเวลา */
    private const RATE_LIMIT_MAX_FAILURES = 5;

    /**
     * index(request)
     * แสดงหน้าชำระเงินของคอนเสิร์ต พร้อมข้อมูลผู้จัด
     * param : request
     * return : View booking.payment
     */
    public function index(Request $request)
    {
        $concertId = (int) $request->query('concert_id');

        $concert = $concertId
            ? Concert::with('organization')->where('concert_id', $concertId)->where('is_deleted', 0)->firstOrFail()
            : Concert::with('organization')->where('status', 'ON_SALE')->where('is_deleted', 0)->orderBy('event_datetime')->first();

        return view('booking.payment', [
            'concert'      => $concert,
            'organization' => $concert?->organization,
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
     * processPayment(request, bookingId)
     * ตรวจ rate limit แล้วสร้างรายการชำระเงิน (บันทึก IP และ device fingerprint ใน payment_metadata)
     * param : request, bookingId
     * return : Payment ที่สร้างขึ้น
     */
    public function processPayment(Request $request, int $bookingId): \App\Models\Payment
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

        return \App\Models\Payment::create([
            'booking_id'       => $bookingId,
            'amount'           => $booking->total_amount,
            'method'           => $request->method,
            'status'           => 'PENDING',
            'payment_metadata' => [
                'ipAddress'         => $ipAddress,
                'deviceFingerprint' => $deviceFingerprint,
            ],
            'created_at'       => now(),
        ]);
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
                'order_code'                   => 'ORD-' . strtoupper(\Illuminate\Support\Str::random(8)),
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
}
