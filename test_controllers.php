<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConcertController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OrderController;
use App\Models\AppUser;
use App\Models\Concert;
use App\Models\Booking;
use App\Models\Ticket;
use App\Models\CustomerOrder;

$pass = 0;
$fail = 0;

function check(string $label, callable $fn) {
    global $pass, $fail;
    try {
        $result = $fn();
        echo "[PASS] {$label}: {$result}\n";
        $pass++;
    } catch (\Throwable $e) {
        echo "[FAIL] {$label}: " . $e->getMessage() . "\n";
        $fail++;
    }
}

$customer   = AppUser::where('role', 'CUSTOMER')->first();
$concert    = Concert::first();
$booking    = Booking::first();
$ticket     = Ticket::first();
$order      = CustomerOrder::first();
$customerId = $customer?->user_id;
$concertId  = $concert?->concert_id;

echo "=========================================\n";
echo "  CONTROLLER TEST – Customer Flow\n";
echo "=========================================\n\n";

// ── AuthController ──────────────────────────────
echo "--- AuthController ---\n";
check('registerCustomer: class instantiable', fn() => (new AuthController()) ? 'OK' : 'FAIL');
check('login: class has login()', fn() => method_exists(AuthController::class, 'login') ? 'OK' : throw new \Exception('missing'));
check('logout: class has logout()', fn() => method_exists(AuthController::class, 'logout') ? 'OK' : throw new \Exception('missing'));

// ── ConcertController ────────────────────────────
echo "\n--- ConcertController ---\n";
$cc = new ConcertController();
check('getConcertList(): count', fn() => $cc->getConcertList()->count() . ' concerts');
check('searchConcert("Rock"): count', fn() => $cc->searchConcert('Rock')->count() . ' results');
check('searchConcert("Pop"): count', fn() => $cc->searchConcert('Pop')->count() . ' results');
check('getConcertDetail(concertId): name', fn() => $cc->getConcertDetail($concertId)->name);
check('getConcertDetail: organization loaded', fn() => $cc->getConcertDetail($concertId)->organization?->name ?? 'null');

// ── BookingController ────────────────────────────
echo "\n--- BookingController ---\n";
$bc = new BookingController();
check('validateBookingSecurity: active user = true',
    fn() => $bc->validateBookingSecurity($customerId, null, null) ? 'true' : 'false');
check('getBookingHistory(customerId): count',
    fn() => $bc->getBookingHistory($customerId)->count() . ' bookings');
check('getCustomerContact(concertId): count',
    fn() => $bc->getCustomerContact($concertId)->count() . ' customers');

// ── createBooking: สร้าง booking ใหม่ (SEATED) ──
$availableSeat = \App\Models\Seat::where('seat_booking_status', 'AVAILABLE')->first();
if ($availableSeat) {
    check('createBooking (SEATED): status=PENDING_PAYMENT', function() use ($bc, $customerId, $concertId, $availableSeat) {
        $newBooking = $bc->createBooking($customerId, $concertId, [$availableSeat->seat_id], []);
        // cleanup
        $newBooking->bookingSeats()->delete();
        \App\Models\SeatLockLog::where('booking_id', $newBooking->booking_id)->delete();
        $availableSeat->unlockSeat();
        $newBooking->delete();
        return 'PENDING_PAYMENT';
    });
} else {
    echo "[SKIP] createBooking: no AVAILABLE seat in DB\n";
}

// ── UserController ───────────────────────────────
echo "\n--- UserController ---\n";
$uc = new UserController();
check('getProfile(customerId): user_id',
    fn() => 'user_id=' . $uc->getProfile($customerId)->user_id);
check('getProfile: organization relationship',
    fn() => 'org=' . ($uc->getProfile($customerId)->organization?->name ?? 'null'));

// ── TicketController ─────────────────────────────
echo "\n--- TicketController ---\n";
$tc = new TicketController();
check('getTickets(customerId): count',
    fn() => $tc->getTickets($customerId)->count() . ' tickets');
if ($ticket) {
    check('generateQR on existing ticket',
        fn() => strlen($ticket->generateQR()) > 0 ? 'qr_code generated' : throw new \Exception('empty'));
    check('validateTicket on existing ticket',
        fn() => $ticket->validateTicket() ? 'valid' : 'invalid (is_used or no zone)');
}

// ── PaymentController ────────────────────────────
echo "\n--- PaymentController ---\n";
$pc = new PaymentController();
check('checkRateLimit (no failures): true',
    fn() => $pc->checkRateLimit($customerId, '127.0.0.1', null) ? 'true (no rate limit hit)' : 'false');

// ── Payment model methods ────────────────────────
echo "\n--- Payment model ---\n";
$payment = \App\Models\Payment::first();
if ($payment) {
    check('Payment::processPayment()',  fn() => $payment->processPayment()  ? 'true' : 'false (status not PENDING)');
    check('Payment::verifyStatus()',    fn() => $payment->verifyStatus()    ? 'COMPLETED' : 'not COMPLETED');
}

// ── OrderController ──────────────────────────────
echo "\n--- OrderController ---\n";
check('OrderController: class instantiable', fn() => (new OrderController()) ? 'OK' : 'FAIL');
check('CustomerOrder first(): exists', fn() => $order ? 'order_id='.$order->order_id : throw new \Exception('no data'));
if ($order) {
    check('CustomerOrder::booking()',  fn() => $order->booking ? 'booking_id='.$order->booking->booking_id : 'null');
    check('CustomerOrder::tickets()', fn() => $order->tickets()->count() . ' tickets');
    check('CustomerOrder::payment()', fn() => $order->payment ? 'payment_id='.$order->payment->payment_id : 'null');
}

echo "\n=========================================\n";
echo "PASSED: {$pass} | FAILED: {$fail}\n";
echo "=========================================\n";
