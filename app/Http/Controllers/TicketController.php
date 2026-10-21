<?php

/**
 * TicketController
 * สร้างและดึงข้อมูลบัตร (E-Ticket) ของคำสั่งซื้อ
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 18/20.2569
 * Updated : 21/10/2569 67160279 กนกพล แซ่ตั๊น
 */

namespace App\Http\Controllers;

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
     * สร้างบัตรจากคำสั่งซื้อ พร้อม ticketCode และ snapshot แล้วเปลี่ยนที่นั่งเป็น SOLD
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
        $order = CustomerOrder::with([
            'booking.bookingSeats.seat.zone',
            'booking.bookingStandingItems.zone',
            'booking.concert',
        ])->findOrFail($orderId);
        $booking = $order->booking;
        $concert = Concert::query()
            ->whereKey($booking->concert_id)
            ->lockForUpdate()
            ->firstOrFail();
        $concertCode = $this->normalizeConcertPublicCode($concert->concert_code);
        $lastSequence = $this->findLastTicketSequence($concertCode);

        $tickets = collect();

        // ── SEATED tickets ──────────────────────────────────────
        foreach ($booking->bookingSeats as $bookingSeat) {
            $seat = $bookingSeat->seat;
            $zone = $seat->zone;
            $lastSequence++;

            if ($lastSequence > 999999) {
                throw new RuntimeException("Ticket sequence is exhausted for concert {$concertCode}.");
            }

            $ticket = Ticket::create([
                'order_id' => $orderId,
                'zone_id' => $zone->zone_id,
                'seat_id' => $seat->seat_id,
                'qr_code' => hash('sha256', $orderId.$seat->seat_id.Str::uuid()),
                'is_used' => false,
                'ticket_code' => sprintf('TKT-%s-%06d', $concertCode, $lastSequence),
                'quantity' => null,
                'zone_name_snapshot' => $bookingSeat->zone_name_snapshot,
                'seat_label_snapshot' => $bookingSeat->seat_label_snapshot,
                'unit_price_snapshot' => $bookingSeat->unit_price_snapshot,
                'created_at' => now(),
            ]);

            // mark seat as SOLD
            $seat->confirmSeat();

            $tickets->push($ticket);
        }

        foreach ($booking->bookingStandingItems as $bookingStandingItem) {
            $zone = $bookingStandingItem->zone;

            if (! $zone || $zone->zone_type !== 'STANDING') {
                throw new RuntimeException('A standing booking references an invalid zone.');
            }

            for ($ticketIndex = 0; $ticketIndex < $bookingStandingItem->quantity; $ticketIndex++) {
                $lastSequence++;

                if ($lastSequence > 999999) {
                    throw new RuntimeException("Ticket sequence is exhausted for concert {$concertCode}.");
                }

                $ticket = Ticket::create([
                    'order_id' => $orderId,
                    'zone_id' => $zone->zone_id,
                    'seat_id' => null,
                    'qr_code' => hash('sha256', $orderId.$zone->zone_id.Str::uuid()),
                    'is_used' => false,
                    'ticket_code' => sprintf('TKT-%s-%06d', $concertCode, $lastSequence),
                    'quantity' => 1,
                    'zone_name_snapshot' => $bookingStandingItem->zone_name_snapshot,
                    'seat_label_snapshot' => 'บัตรยืน',
                    'unit_price_snapshot' => $bookingStandingItem->unit_price_snapshot,
                    'created_at' => now(),
                ]);

                $tickets->push($ticket);
            }
        }

        return $tickets;
    }

    /**
     * normalizeConcertPublicCode(concertCode)
     * จัดรูป concert_code แบบเดิมให้เป็น 1 ตัวอักษรตามด้วยตัวเลข 6 หลัก
     * param : concertCode
     * return : string
     */
    private function normalizeConcertPublicCode(string $concertCode): string
    {
        if (! preg_match('/^([A-Z])([0-9]{1,6})$/i', $concertCode, $matches) || (int) $matches[2] === 0) {
            throw new RuntimeException("Concert code [{$concertCode}] cannot be used in a ticket code.");
        }

        return strtoupper($matches[1]).str_pad($matches[2], 6, '0', STR_PAD_LEFT);
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
            ->whereHas('order.booking', function ($q) use ($customerId) {
                $q->where('user_id', $customerId)
                    ->where('status', 'CONFIRMED');
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
            ->whereHas('order.booking', function ($query) {
                $query->where('user_id', Auth::id())
                    ->where('status', 'CONFIRMED');
            })
            ->orderByDesc('ticket_id')
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
                    'zone' => $ticket->zone_name_snapshot ?: $ticket->zone?->name ?: 'ไม่ระบุโซน',
                    'seat' => $ticket->seat_label_snapshot
                        ?: ($ticket->seat ? $ticket->seat->row.$ticket->seat->number : 'บัตรยืน'),
                ];
            });

        return view('account.my-ticket', compact('tickets'));
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
            ->with(['zone', 'seat', 'order.booking.concert.organization'])
            ->where('ticket_code', $ticketCode)
            ->whereHas('order.booking', function ($query) {
                $query->where('user_id', Auth::id())
                    ->where('status', 'CONFIRMED');
            })
            ->firstOrFail();

        $order = $ticket->order;
        $concert = $order?->booking?->concert;
        $eventDate = $concert?->event_datetime ?? $order?->event_datetime_snapshot;
        $organization = $concert?->organization;

        try {
            $qrCode = $this->generateTicketQrCode($ticket->ticket_code);
        } catch (Throwable $exception) {
            Log::error('Unable to generate ticket QR code.', [
                'ticket_id' => $ticket->ticket_id,
                'exception' => $exception,
            ]);
            $qrCode = null;
        }

        return response()->json([
            'ticket_code' => $ticket->ticket_code,
            'concert_name' => $order?->concert_name_snapshot ?: $concert?->name ?: 'ไม่ระบุชื่อคอนเสิร์ต',
            'event_date' => $eventDate?->thDateLong() ?? 'ไม่ระบุวันที่',
            'event_time' => $eventDate?->format('H:i') ?? 'ไม่ระบุเวลา',
            'zone' => $ticket->zone_name_snapshot ?: $ticket->zone?->name ?: 'ไม่ระบุโซน',
            'seat' => $ticket->seat_label_snapshot
                ?: ($ticket->seat ? $ticket->seat->row.$ticket->seat->number : 'บัตรยืน'),
            'qr_code' => $qrCode,
            'contact' => [
                'phone' => $organization?->phone ?: '-',
                'line' => $organization?->line ?: '-',
                'facebook' => $organization?->facebook ?: '-',
                'email' => $organization?->email ?: '-',
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
            ->whereHas('booking', fn ($q) => $q->where('user_id', $userId))
            ->orderBy('order_date', 'desc')
            ->paginate(self::ORDERS_PER_PAGE);

        return view('account.order-history', compact('orders'));
    }
}
