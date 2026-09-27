<?php

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * TicketController
 * Spec: generateTickets(), getTickets()
 * Author : AI Agent
 * Created : 27/09/2026
 */
class TicketController extends Controller
{
    /**
     * generateTickets(orderID): List<Ticket>
     * สร้างบัตรจาก CustomerOrder พร้อม ticketCode และ Snapshot
     * SEATED → สร้าง Ticket ที่มี seat_id + seat_label_snapshot
     * STANDING → สร้าง Ticket ที่ไม่มี seat_id แต่มี quantity
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
     * getTickets(customerID): List<Ticket>
     * ดึง E-Ticket ของ Customer (ทั้งแบบ SEATED ที่มี seat_id และแบบ STANDING ที่มี quantity)
     * ผ่าน Booking → CustomerOrder → Ticket
     */
    public function getTickets(int $customerId): \Illuminate\Support\Collection
    {
        return Ticket::with(['zone', 'seat', 'order.booking.concert'])
            ->whereHas('order.booking', function ($q) use ($customerId) {
                $q->where('customer_id', $customerId)
                  ->where('status', 'CONFIRMED');
            })
            ->get();
    }

    /**
     * history() – แสดงหน้าประวัติ Order (HTTP endpoint)
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
            ->whereHas('booking', fn($q) => $q->where('customer_id', $userId))
            ->orderBy('order_date', 'desc')
            ->paginate(10);

        return view('orders.history', compact('orders'));
    }
}
