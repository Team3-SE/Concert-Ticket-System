<?php

/**
 * TicketController
 * สร้างและดึงข้อมูลบัตร (E-Ticket) ของคำสั่งซื้อ
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 18/10/2569
 * Updated : 21/10/2569 67160279 กนกพล แซ่ตั๊น
 * Updated : 21/10/2569 67160292 ธีรเมธ พลสาย
 * Updated : 11/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Concert;
use App\Models\CustomerOrder;
use App\Models\Ticket;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TicketController extends Controller
{
    /* จำนวนคำสั่งซื้อต่อหน้าในหน้าประวัติการสั่งซื้อ */
    private const ORDERS_PER_PAGE = 10;

    private const TICKETS_PER_PAGE = 10;

    private const QR_CODE_SIZE = 320;

    private const THAI_MONTHS = [
        1 => 'ม.ค.',
        2 => 'ก.พ.',
        3 => 'มี.ค.',
        4 => 'เม.ย.',
        5 => 'พ.ค.',
        6 => 'มิ.ย.',
        7 => 'ก.ค.',
        8 => 'ส.ค.',
        9 => 'ก.ย.',
        10 => 'ต.ค.',
        11 => 'พ.ย.',
        12 => 'ธ.ค.',
    ];

    /**
     * generateTickets(orderId)
     * สร้างบัตรจากคำสั่งซื้อ 1 ใบต่อ 1 ที่นั่ง/บัตรยืน พร้อม ticketCode และ slot_no (ที่นั่งเปลี่ยนเป็น sold ตอน Booking::confirmBooking แล้ว)
     * param : orderId
     * return : Collection ของ Ticket
     */
    public function generateTickets(int $orderId): Collection
    {
        return DB::transaction(fn () => $this->createTicketsForOrder($orderId));
    }

    /**
     * createTicketsForOrder(orderId)
     * สร้างตั๋วและเลขลำดับขณะล็อกคอนเสิร์ตใน transaction
     * param : orderId
     * return : Collection ของ Ticket
     */
    private function createTicketsForOrder(int $orderId): Collection
    {
        $order = CustomerOrder::with(['booking.bookingSeats.seat', 'booking.zone'])->findOrFail($orderId);
        $booking = $order->booking;
        $zone = $booking->zone;
        $concert = Concert::query()
            ->whereKey($booking->concertId())
            ->lockForUpdate()
            ->firstOrFail();
        $concertCode = $concert->publicCode();   // C0024 -> C000024 (Concert::publicCode)
        $lastSequence = $this->findLastTicketSequence($concertCode);

        // schema 3.4.2: 1 การจอง = 1 โซน, ตั๋ว 1 แถว = 1 ใบ, slot_no = ลำดับในคำสั่งซื้อ 1..quantity
        $items = $booking->isStanding()
            ? collect(range(1, (int) $booking->standing_quantity))->map(fn () => ['seat' => null, 'label' => 'บัตรยืน'])
            : $booking->bookingSeats->map(fn ($bookingSeat) => [
                'seat'  => $bookingSeat->seat,
                'label' => $bookingSeat->seat_label_snapshot,
            ]);

        if ($items->isEmpty()) {
            throw new RuntimeException("Order {$order->order_code} has no seats or standing tickets.");
        }
        if ($booking->isStanding() && (! $zone || ! $zone->isStanding())) {
            throw new RuntimeException('A standing booking references an invalid zone.');
        }

        $tickets = collect();
        foreach ($items->values() as $index => $item) {
            $lastSequence++;

            if ($lastSequence > 999999) {
                throw new RuntimeException("Ticket sequence is exhausted for concert {$concertCode}.");
            }

            $seatId = $item['seat']?->s_id;
            $tickets->push(Ticket::create([
                't_order_id'          => $orderId,
                't_zone_id'           => $booking->b_zone_id,
                't_seat_id'           => $seatId,
                'qr_code'             => hash('sha256', $orderId.($seatId ?? $booking->b_zone_id).Str::uuid()),
                'is_used'             => false,
                'ticket_code'         => sprintf('TKT-%s-%06d', $concertCode, $lastSequence),
                'seat_label_snapshot' => $item['label'],
                'status'              => Ticket::STATUS_VALID,
                'slot_no'             => $index + 1,
                'created_at'          => now(),
            ]));
        }

        return $tickets;
    }

    /**
     * findLastTicketSequence(concertCode)
     * หาเลข ticket sequence ล่าสุดเพื่อเริ่ม counter สำหรับคอนเสิร์ตนี้
     * param : concertCode
     * return : int
     */
    private function findLastTicketSequence(string $concertCode): int
    {
        $pattern = '/^TKT-'.preg_quote($concertCode, '/').'-([0-9]{6})$/i';
        $ticketCodes = Ticket::query()
            ->where('ticket_code', 'like', "TKT-{$concertCode}-%")
            ->pluck('ticket_code');
        $lastSequence = 0;

        foreach ($ticketCodes as $ticketCode) {
            if (preg_match($pattern, $ticketCode, $matches)) {
                $lastSequence = max($lastSequence, (int) $matches[1]);
            }
        }

        return $lastSequence;
    }

    /**
     * getTickets(customerId)
     * ดึงบัตรทั้งหมดของลูกค้าที่การจองได้รับการยืนยันแล้ว
     * param : customerId
     * return : Collection ของ Ticket
     */
    public function getTickets(int $customerId): Collection
    {
        return Ticket::with(['zone', 'seat', 'order.booking.concert'])
            ->where('status', Ticket::STATUS_VALID)
            ->whereHas('order.booking', function ($q) use ($customerId) {
                $q->where('b_user_id', $customerId)
                    ->where('status', Booking::STATUS_CONFIRMED);
            })
            ->get();
    }

    /**
     * index()
     * แสดงตั๋วของผู้ใช้ที่เข้าสู่ระบบ โดยเรียงตามวันที่สร้างบัตร
     * param : -
     * return : View account.my-ticket
     */
    public function index(): View
    {
        $tickets = Ticket::query()
            ->with(['zone', 'seat', 'order.booking.concert'])
            ->whereNotNull('ticket_code')
            ->where('status', Ticket::STATUS_VALID)   // ไม่แสดงตั๋วที่ถูกยกเลิก/ออกใบใหม่แทนแล้ว
            ->whereHas('order.booking', function ($query) {
                $query->where('b_user_id', Auth::id())
                    ->where('status', Booking::STATUS_CONFIRMED);
            })
            ->orderByDesc('t_id')
            ->paginate(self::TICKETS_PER_PAGE)
            ->through(function (Ticket $ticket) {
                $order = $ticket->order;
                $concert = $order?->booking?->concert;
                $eventDate = $concert?->event_datetime ?? $order?->event_datetime_snapshot;

                return [
                    'ticket_code' => $ticket->ticket_code,
                    'concert_name' => $order?->concert_name_snapshot ?: $concert?->name ?: 'ไม่ระบุชื่อคอนเสิร์ต',
                    'event_day' => $eventDate?->format('d') ?? '--',
                    'event_month' => $eventDate ? self::THAI_MONTHS[$eventDate->month] : '',
                    'event_time' => $eventDate?->format('H:i') ?? 'ไม่ระบุเวลา',
                    'venue' => $order?->venue_snapshot ?: $concert?->venue ?: 'ไม่ระบุสถานที่',
                    'zone' => $ticket->zoneName() ?: 'ไม่ระบุโซน',
                    'seat' => $ticket->seat_label_snapshot ?: ($ticket->seat?->label() ?: 'บัตรยืน'),
                ];
            });

        return view('user.account.my-ticket', compact('tickets'));
    }

    /**
     * show(ticketCode)
     * ส่งข้อมูลและ QR ของตั๋วให้เจ้าของตั๋วที่เข้าสู่ระบบเท่านั้น
     * param : ticketCode
     * return : JsonResponse
     */
    public function show(string $ticketCode): JsonResponse
    {
        $ticket = Ticket::query()
            ->with(['zone', 'seat', 'order.booking.concert'])
            ->where('ticket_code', $ticketCode)
            ->where('status', Ticket::STATUS_VALID)
            ->whereHas('order.booking', function ($query) {
                $query->where('b_user_id', Auth::id())
                    ->where('status', Booking::STATUS_CONFIRMED);
            })
            ->firstOrFail();

        $order = $ticket->order;
        $concert = $order?->booking?->concert;
        $eventDate = $concert?->event_datetime ?? $order?->event_datetime_snapshot;

        try {
            $qrCode = $this->generateTicketQrCode($ticket->ticket_code);
        } catch (Throwable $exception) {
            Log::error('Unable to generate ticket QR code.', [
                'ticket_id' => $ticket->t_id,
                'exception' => $exception,
            ]);
            $qrCode = null;
        }

        return response()->json([
            'ticket_code' => $ticket->ticket_code,
            'concert_name' => $order?->concert_name_snapshot ?: $concert?->name ?: 'ไม่ระบุชื่อคอนเสิร์ต',
            'event_date' => $eventDate?->thDateLong() ?? 'ไม่ระบุวันที่',
            'event_time' => $eventDate?->format('H:i') ?? 'ไม่ระบุเวลา',
            'zone' => $ticket->zoneName() ?: 'ไม่ระบุโซน',
            'seat' => $ticket->seat_label_snapshot ?: ($ticket->seat?->label() ?: 'บัตรยืน'),
            'qr_code' => $qrCode,
            // schema 3.4.2: ช่องทางติดต่อผู้จัดเก็บไว้ที่คอนเสิร์ต
            'contact' => [
                'phone' => $concert?->phone ?: '-',
                'line' => $concert?->line ?: '-',
                'facebook' => $concert?->facebook ?: '-',
                'email' => $concert?->email ?: '-',
            ],
        ]);
    }

    /**
     * generateTicketQrCode(ticketCode)
     * สร้าง QR Code ที่เข้ารหัส ticket_code และคืนค่าเป็น data URI
     * param : ticketCode
     * return : string
     */
    private function generateTicketQrCode(string $ticketCode): string
    {
        $qrCode = QrCode::create($ticketCode)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
            ->setSize(self::QR_CODE_SIZE)
            ->setMargin(12);

        return (new PngWriter)->write($qrCode)->getDataUri();
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
            ->whereHas('booking', fn ($q) => $q->where('b_user_id', $userId))
            ->orderBy('order_date', 'desc')
            ->paginate(self::ORDERS_PER_PAGE);

        return view('user.account.order-history', compact('orders'));
    }
}
