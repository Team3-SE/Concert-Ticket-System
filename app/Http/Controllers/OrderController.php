<?php

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function history()
    {
        $customer = Auth::user()->customer;

        $orders = CustomerOrder::whereHas('booking', function ($query) use ($customer) {
                $query->where('customer_id', $customer->customer_id);
            })
            ->with([
                'booking.concert',
                'booking.bookingSeats',
                'tickets',
                'payment',
            ])
            ->orderBy('order_date', 'desc')
            ->paginate(10);

        return view('orders.history', compact('orders'));
    }
}
