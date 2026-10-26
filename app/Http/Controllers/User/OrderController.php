<?php

/**
 * OrderController
 * แสดงประวัติการสั่งซื้อของผู้ใช้ที่เข้าสู่ระบบ
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 16/10/2569
 * Updated : 21/10/2569 67160292 ธีรเมธ พลสาย
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
     * param : -
     * return : View account.order-history
     */
    public function history()
    {
        $userId = Auth::id();

        $orders = CustomerOrder::with([
            'booking.concert',
            'booking.bookingSeats',
            'booking.bookingStandingItems',
            'tickets.zone',
            'tickets.seat',
            'payment',
        ])
            ->whereHas('booking', fn ($q) => $q->where('user_id', $userId))
            ->orderBy('order_date', 'desc')
            ->paginate(self::ORDERS_PER_PAGE)
            ->through(function (CustomerOrder $order) {
                $booking = $order->booking;
                $concert = $booking?->concert;
                $seats = $booking?->bookingSeats ?? collect();
                $seatGroups = $seats->groupBy(fn ($seat) => $seat->zone_name_snapshot ?? '–');
                $zones = $seatGroups->map(function ($zoneSeats, $zoneName) {
                    $seatLabels = $zoneSeats->pluck('seat_label_snapshot')->filter()->join(', ');

                    return [
                        'name' => Str::startsWith($zoneName, 'โซน') ? $zoneName : 'โซน '.$zoneName,
                        'details' => $seatLabels ?: '–',
                    ];
                })->values();

                foreach ($booking?->bookingStandingItems ?? collect() as $standingItem) {
                    $zoneName = $standingItem->zone_name_snapshot ?? '–';
                    $zones->push([
                        'name' => Str::startsWith($zoneName, 'โซน') ? $zoneName : 'โซน '.$zoneName,
                        'details' => 'บัตรยืน x '.$standingItem->quantity,
                    ]);
                }

                return [
                    'order_code' => $order->order_code ?? substr($order->order_id, 0, 8),
                    'concert_name' => $order->concert_name_snapshot ?? $concert?->name ?? '–',
                    'venue' => $order->venue_snapshot ?? $concert?->venue ?? '–',
                    'event_datetime' => $this->formatThaiDateTime(
                        $order->event_datetime_snapshot ?? $concert?->event_datetime
                    ),
                    'zones' => $zones,
                    'ticket_count' => $order->tickets?->count() ?? $seats->count(),
                    'order_date' => $this->formatThaiDateTime($order->order_date, true),
                    'total_amount' => number_format((float) ($order->payment?->amount ?? $booking?->total_amount ?? 0), 0),
                ];
            });

        return view('user.account.order-history', compact('orders'));
    }

    private function formatThaiDateTime(mixed $value, bool $convertFromUtc = false): string
    {
        if (! $value) {
            return '–';
        }

        $date = Carbon::parse($value);
        if ($convertFromUtc) {
            $date->setTimezone('Asia/Bangkok');
        }

        $months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
            'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        return $date->day.' '.$months[$date->month - 1].' '.($date->year + 543).' '.$date->format('H:i');
    }
}
