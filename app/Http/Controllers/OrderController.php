<?php

/**
 * OrderController
 * แสดงประวัติการสั่งซื้อของผู้ใช้ที่เข้าสู่ระบบ
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 16/10/2569
 */

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /* จำนวนคำสั่งซื้อต่อหน้าในหน้าประวัติการสั่งซื้อ */
    private const ORDERS_PER_PAGE = 10;

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
                'tickets.zone',
                'tickets.seat',
                'payment',
            ])
            ->whereHas('booking', fn($q) => $q->where('user_id', $userId))
            ->orderBy('order_date', 'desc')
            ->paginate(self::ORDERS_PER_PAGE);

        return view('account.order-history', compact('orders'));
    }
}
