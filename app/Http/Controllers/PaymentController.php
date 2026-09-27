<?php

namespace App\Http\Controllers;

use App\Models\Concert;
use Illuminate\Http\Request;

/**
 * PaymentController
 * Spec: processPayment(), verifyPayment(), checkRateLimit(), completePayment()
 * Author : AI Agent
 * Updated : 27/09/2026 – fixed to use organization relationship
 */
class PaymentController extends Controller
{
    /**
     * index() – แสดงหน้า Payment (UI)
     */
    public function index(Request $request)
    {
        $concertId = (int) $request->query('concert_id');

        $concert = $concertId
            ? Concert::with('organization')->where('concert_id', $concertId)->where('is_deleted', 0)->firstOrFail()
            : Concert::with('organization')->where('status', 'ON_SALE')->where('is_deleted', 0)->orderBy('event_datetime')->first();

        return view('payment', [
            'concert'      => $concert,
            'organization' => $concert?->organization,
        ]);
    }

    /**
     * checkRateLimit(customerID, ipAddress, deviceFingerprint): Boolean
     * นับการชำระเงินที่ล้มเหลวต่อเนื่องตาม Customer, IP และอุปกรณ์ภายในช่วงเวลาที่กำหนด
     */
    public function checkRateLimit(int $customerId, string $ipAddress, ?string $deviceFingerprint): bool
    {
        $windowMinutes = 15;
        $maxFailures   = 5;

        $failCount = \App\Models\Payment::whereHas('booking', fn($q) => $q->where('customer_id', $customerId))
            ->where('status', 'FAILED')
            ->where('created_at', '>=', now()->subMinutes($windowMinutes))
            ->where(function ($q) use ($ipAddress, $deviceFingerprint) {
                $q->whereJsonContains('payment_metadata->ipAddress', $ipAddress);
                if ($deviceFingerprint) {
                    $q->orWhereJsonContains('payment_metadata->deviceFingerprint', $deviceFingerprint);
                }
            })
            ->count();

        return $failCount < $maxFailures;
    }

    /**
     * processPayment(bookingID, method): Payment
     * บันทึก ipAddress/deviceFingerprint ลงใน paymentMetadata
     * ตรวจ rate limit แล้วสร้าง Payment
     */
    public function processPayment(Request $request, int $bookingId): \App\Models\Payment
    {
        $request->validate([
            'method' => ['required', 'in:PROMPTPAY,CREDIT_CARD'],
        ]);

        $booking = \App\Models\Booking::findOrFail($bookingId);
        $ipAddress = $request->ip();
        $deviceFingerprint = $request->header('X-Device-Fingerprint');

        if (!$this->checkRateLimit($booking->customer_id, $ipAddress, $deviceFingerprint)) {
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
     * verifyPayment(paymentID): Boolean
     * ตรวจสอบสถานะธุรกรรมจาก Payment Provider
     * บันทึก declineCode/cardFingerprint ลงใน paymentMetadata
     */
    public function verifyPayment(int $paymentId): bool
    {
        $payment = \App\Models\Payment::findOrFail($paymentId);
        // TODO: เรียก Payment Provider API จริง
        // ตัวอย่าง: ส่งคืน true ถ้า COMPLETED
        return $payment->status === 'COMPLETED';
    }

    /**
     * completePayment(paymentID): CustomerOrder
     * เมื่อ Payment COMPLETED → ยืนยัน Booking → สร้าง CustomerOrder พร้อม Snapshot
     */
    public function completePayment(int $paymentId): \App\Models\CustomerOrder
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($paymentId) {
            $payment = \App\Models\Payment::with('booking.concert.organization')->findOrFail($paymentId);
            $booking = $payment->booking;
            $concert = $booking->concert;
            $customer = \App\Models\AppUser::find($booking->customer_id);

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
                'customer_fiestname_snapshot'  => $customer?->first_name ?? '',
                'customer_lastname_snapshot'   => $customer?->last_name ?? '',
                'customer_phone_snapshot'      => $customer?->phone ?? '',
                'order_date'                   => now(),
            ]);

            // สร้าง Ticket ผ่าน TicketController
            (new TicketController())->generateTickets($order->order_id);

            return $order;
        });
    }
}
