<?php

/**
 * OrderController
 * แสดงประวัติการสั่งซื้อของผู้ใช้ที่เข้าสู่ระบบ
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 16/10/2569
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CustomerOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /* จำนวนคำสั่งซื้อต่อหน้าในหน้าประวัติการสั่งซื้อ */
    private const ORDERS_PER_PAGE = 10;

    /**
     * history()
     * แสดงประวัติการสั่งซื้อของผู้ใช้ที่เข้าสู่ระบบ แบบแบ่งหน้า
     *
     * @return View account.order-history
     */
    public function history()
    {
        $userId = Auth::id();

        $orders = CustomerOrder::with([
            'booking.concert',
            'booking.bookingSeats',
            'validTickets',
            'payment',
        ])
            ->whereHas('booking', fn ($q) => $q->where('b_user_id', $userId))
            ->orderBy('order_date', 'desc')
            ->paginate(self::ORDERS_PER_PAGE)
            ->through(function (CustomerOrder $order) {
                $booking = $order->booking;
                $concert = $booking?->concert;
                $seats = $booking?->bookingSeats ?? collect();

                // schema 3.4.2: 1 คำสั่งซื้อ = 1 โซน ชื่อโซนเก็บไว้ที่ user_order.zone_name_snapshot
                $zoneName = (string) ($order->zone_name_snapshot ?: $booking?->zone_name_snapshot ?: '–');
                $zones = collect([[
                    'name' => Str::startsWith($zoneName, 'โซน') ? $zoneName : 'โซน '.$zoneName,
                    'details' => $booking?->isStanding()
                        ? 'บัตรยืน x '.(int) $booking->standing_quantity
                        : ($seats->pluck('seat_label_snapshot')->filter()->join(', ') ?: '–'),
                ]]);

                return [
                    'order_code' => $order->order_code ?? (string) $order->uo_id,
                    'concert_name' => $order->concert_name_snapshot ?? $concert?->name ?? '–',
                    'venue' => $order->venue_snapshot ?? $concert?->venue ?? '–',
                    'event_datetime' => $this->formatThaiDateTime(
                        $order->event_datetime_snapshot ?? $concert?->event_datetime
                    ),
                    'zones' => $zones,
                    'ticket_count' => $order->validTickets?->count() ?: (int) $order->quantity,
                    'order_date' => $this->formatThaiDateTime($order->order_date),
                    'total_amount' => number_format((float) ($order->payment?->amount ?? $booking?->total_amount ?? 0), 0),
                ];
            });

        return view('user.account.order-history', compact('orders'));
    }

    /**
     * formatThaiDateTime($value)
     * แปลงวันเวลาเป็นรูปแบบไทย เช่น 11 พ.ย. 2569 16:30 (ระบบเก็บเวลาเป็นเวลาไทยอยู่แล้ว)
     *
     * @param mixed $value ค่าที่ส่งมา
     * @return string ข้อความตามที่อธิบายข้างต้น
     */
    private function formatThaiDateTime(mixed $value): string
    {
        if (! $value) {
            return '–';
        }

        $date = Carbon::parse($value);

        $months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
            'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        return $date->day.' '.$months[$date->month - 1].' '.($date->year + 543).' '.$date->format('H:i');
    }
}
