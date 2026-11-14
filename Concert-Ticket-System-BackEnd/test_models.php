<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

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

// ── Concert ──────────────────────────────────────
$concert = \App\Models\Concert::first();
check('Concert::first() exists', fn() => $concert ? 'yes (id:'.$concert->concert_id.')' : throw new \Exception('no data'));
check('Concert::getDetails()', fn() => $concert->getDetails());
check('Concert::organization()', fn() => $concert->organization ? $concert->organization->name : 'null (no org linked)');
check('Concert::zones()->count()', fn() => $concert->zones()->count() . ' zones');
check('Concert::bookings()->count()', fn() => $concert->bookings()->count() . ' bookings');

// ── Organization (was Company) ───────────────────
$org = \App\Models\Organization::first();
check('Organization::first() exists', fn() => $org ? 'yes (id:'.$org->organization_id.')' : throw new \Exception('no data'));
check('Organization::getOrganizationInfo()', fn() => $org->getOrganizationInfo());
check('Organization::listEvents()->count()', fn() => $org->listEvents()->count() . ' concerts');

// ── Zone ─────────────────────────────────────────
$zone = \App\Models\Zone::first();
check('Zone::first() exists', fn() => $zone ? 'yes (id:'.$zone->zone_id.')' : throw new \Exception('no data'));
check('Zone::getAvailableSeats()->count()', fn() => $zone->getAvailableSeats()->count() . ' available seats');
check('Zone::getAvailableCapacity()', fn() => $zone->getAvailableCapacity() . ' capacity remaining');

// ── Seat ─────────────────────────────────────────
$seat = \App\Models\Seat::first();
check('Seat::first() exists', fn() => $seat ? 'yes (id:'.$seat->seat_id.')' : throw new \Exception('no data'));
check('Seat::lockSeat() → LOCKED', function() use ($seat) {
    $seat->lockSeat();
    $seat->refresh();
    return $seat->seat_booking_status;
});
check('Seat::unlockSeat() → AVAILABLE', function() use ($seat) {
    $seat->unlockSeat();
    $seat->refresh();
    return $seat->seat_booking_status;
});

// ── Booking ───────────────────────────────────────
$booking = \App\Models\Booking::first();
check('Booking::first() exists', fn() => $booking ? 'yes (id:'.$booking->booking_id.')' : throw new \Exception('no data'));
check('Booking::calculateTotal()', fn() => 'total=' . $booking->calculateTotal());
check('Booking::checkExpiry()', fn() => $booking->checkExpiry() ? 'Expired' : 'Not Expired');
check('Booking::customer()', fn() => $booking->customer ? 'user_id:'.$booking->customer->user_id : 'null');
check('Booking::concert()', fn() => $booking->concert ? $booking->concert->name : 'null');

// ── AuditLog ──────────────────────────────────────
// No seed data – just test model instantiation
check('AuditLog::getDetails() (no seed data ok)', function() {
    $log = new \App\Models\AuditLog(['action' => 10, 'ip_address' => '127.0.0.1']);
    $log->created_at = now();
    return $log->getDetails();
});

// ── Ticket ────────────────────────────────────────
$ticket = \App\Models\Ticket::first();
check('Ticket::first() exists', fn() => $ticket ? 'yes (id:'.$ticket->ticket_id.')' : throw new \Exception('no data'));
check('Ticket::validateTicket()', fn() => $ticket->validateTicket() ? 'valid' : 'invalid');
check('Ticket::zone()', fn() => $ticket->zone ? 'zone_id:'.$ticket->zone->zone_id : 'null');

// ── User (AppUser) ────────────────────────────────
$user = \App\Models\AppUser::first();
check('AppUser::first() exists', fn() => $user ? 'yes (id:'.$user->user_id.')' : throw new \Exception('no data'));
check('AppUser::isActive()', fn() => $user->isActive() ? 'ACTIVE' : 'NOT ACTIVE');
check('AppUser::role helpers()', fn() => 'isCustomer:'.(int)$user->isCustomer().' isAdmin:'.(int)$user->isAdmin().' isOrganizer:'.(int)$user->isOrganizer());
check('AppUser::organization()', fn() => $user->organization ? $user->organization->name : 'null (no org)');

// ── Customer ──────────────────────────────────────
$customer = \App\Models\Customer::first();
check('Customer::first() exists', fn() => $customer ? 'yes (id:'.$customer->user_id.')' : throw new \Exception('no data'));
check('Customer::viewHistory()->count()', fn() => $customer->viewHistory()->count() . ' bookings');
check('Customer::viewTickets()->count()', fn() => $customer->viewTickets()->count() . ' tickets');
check('Customer::searchConcert("Rock")', fn() => $customer->searchConcert('Rock')->count() . ' results');

echo "\n============================\n";
echo "PASSED: {$pass} | FAILED: {$fail}\n";
