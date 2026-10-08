<?php

/**
 * TicketController
 * สร้างและดึงข้อมูลบัตร (E-Ticket) ของคำสั่งซื้อ
 * Author :
 * Created :
 */

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    /* จำนวนคำสั่งซื้อต่อหน้าในหน้าประวัติการสั่งซื้อ */
    private const ORDERS_PER_PAGE = 10;

    /**
     * generateTickets(orderId)
     * สร้างบัตรจากคำสั่งซื้อ พร้อม ticketCode และ snapshot แล้วเปลี่ยนที่นั่งเป็น SOLD
     * param : orderId
     * return : Collection ของ Ticket
     */
    public function generateTickets(int $orderId): \Illuminate\Support\Collection
    {
        $order   = CustomerOrder::with(['booking.bookingSeats.seat.zone', 'booking.concert'])->findOrFail($orderId);
        $booking = $order->booking;
        $tickets = collect();

        // ── SEATED tickets ──────────────────────────────────────
        foreach ($booking->bookingSeats as $bookingSeat) {
            $seat = $bookingSeat->seat;
            $zone = $seat->zone;

            $ticket = Ticket::create([
                'order_id'             => $orderId,
                'zone_id'              => $zone->zone_id,
                'seat_id'              => $seat->seat_id,
                'qr_code'              => hash('sha256', $orderId . $seat->seat_id . Str::uuid()),
                'is_used'              => false,
                'ticket_code'          => 'TKT-' . strtoupper(Str::random(10)),
                'quantity'             => null,
                'zone_name_snapshot'   => $bookingSeat->zone_name_snapshot,
                'seat_label_snapshot'  => $bookingSeat->seat_label_snapshot,
                'unit_price_snapshot'  => $bookingSeat->unit_price_snapshot,
                'created_at'           => now(),
            ]);

            // mark seat as SOLD
            $seat->confirmSeat();

            $tickets->push($ticket);
        }

        // ── STANDING tickets ────────────────────────────────────
        // Standing zones ไม่มี BookingSeat แต่ดูจาก zone ที่เป็น STANDING ใน concert
        // quantity เก็บไว้ใน Ticket โดยตรง (1 Ticket = หลายใบ STANDING)
        // *** Logic นี้ต้องได้รับ standingItems จาก Booking metadata / session ***
        // TODO: pass standing items explicitly when calling generateTickets in production

        return $tickets;
    }

    /**
     * getTickets(customerId)
     * ดึงบัตรทั้งหมดของลูกค้าที่การจองได้รับการยืนยันแล้ว
     * param : customerId
     * return : Collection ของ Ticket
     */
    public function getTickets(int $customerId): \Illuminate\Support\Collection
    {
        return Ticket::with(['zone', 'seat', 'order.booking.concert'])
            ->whereHas('order.booking', function ($q) use ($customerId) {
                $q->where('user_id', $customerId)
                  ->where('status', 'CONFIRMED');
            })
            ->get();
    }

    /**
     * history()
     * แสดงประวัติการสั่งซื้อของผู้ใช้ที่เข้าสู่ระบบ แบบแบ่งหน้า
     * param : -
     * return : View account.order-history
     */
    public function history()
    {
        $userId = Auth::id();

        $orders = CustomerOrder::with([
                'booking.concert',
                'booking.bookingSeats',
                'tickets',
                'payment',
            ])
            ->whereHas('booking', fn($q) => $q->where('user_id', $userId))
            ->orderBy('order_date', 'desc')
            ->paginate(self::ORDERS_PER_PAGE);

        return view('account.order-history', compact('orders'));
    }
}
