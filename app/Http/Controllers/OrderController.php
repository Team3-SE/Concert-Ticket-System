<?php

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use Illuminate\Support\Facades\Auth;

/**
 * OrderController
 * Routes Order history (E-Ticket) – delegates to TicketController.getTickets()
 * Author : AI Agent
 * Updated : 27/09/2026 – fixed customer_id lookup to use user_id directly
 */
class OrderController extends Controller
{
    /**
     * history() – displayBookingHistory(): void
     * แสดงประวัติ Booking / Order ของ Customer
     */
    public function history()
    {
        $userId = Auth::id();

        $orders = CustomerOrder::with([
                'booking.concert',
                'booking.bookingSeats',
                'tickets.zone',
                'tickets.seat',
                'payment',
            ])
            ->whereHas('booking', fn($q) => $q->where('customer_id', $userId))
            ->orderBy('order_date', 'desc')
            ->paginate(10);

        return view('orders.history', compact('orders'));
    }
}
