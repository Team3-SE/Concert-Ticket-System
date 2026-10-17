<?php
/**
 * Mock Concert Website & Backend (Port 5000)
 * ระบบจำลองเว็บคอนเสิร์ตฝั่งต้นทาง เขียนด้วย PHP Native
 */

// Simple routing based on REQUEST_URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Database file setup (SQLite)
$dbFile = __DIR__ . '/concert.sqlite';
$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// สร้างตารางถ้ายังไม่มี
$db->exec("
    CREATE TABLE IF NOT EXISTS bookings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_ref TEXT UNIQUE,
        concert_title TEXT,
        seat TEXT,
        amount REAL,
        status TEXT DEFAULT 'PENDING',
        payment_id TEXT,
        paid_at TEXT,
        created_at TEXT
    );
    CREATE TABLE IF NOT EXISTS webhook_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_ref TEXT,
        event TEXT,
        signature_valid INTEGER,
        rejection_reason TEXT,
        response_code INTEGER DEFAULT 200,
        payload TEXT,
        received_at TEXT
    );
    CREATE TABLE IF NOT EXISTS concert_payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        booking_id INTEGER NOT NULL,
        provider_payment_id TEXT UNIQUE,
        method TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'PENDING',
        failure_code TEXT,
        created_at TEXT NOT NULL
    );
");

// Migrate: add rejection_reason and response_code if not exist (safe for existing DBs)
try { $db->exec("ALTER TABLE webhook_logs ADD COLUMN rejection_reason TEXT"); } catch(Exception $e) {}
try { $db->exec("ALTER TABLE webhook_logs ADD COLUMN response_code INTEGER DEFAULT 200"); } catch(Exception $e) {}
try { $db->exec("ALTER TABLE webhook_logs ADD COLUMN payment_id TEXT"); } catch(Exception $e) {}

/** Map the gateway payment state to the booking state. */
function bookingStatus(string $gatewayStatus): string
{
    return match ($gatewayStatus) {
        'COMPLETED' => 'PAID',
        'FAILED'    => 'FAILED',
        'EXPIRED'   => 'EXPIRED',
        default     => 'PENDING',
    };
}

/** Replace a Docker-only URL so the browser can use it. */
function browserUrl(?string $url): ?string
{
    return $url === null ? null : str_replace(
        ['http://paymock:80', 'http://paymock'],
        'http://localhost:8081',
        $url
    );
}

/** Create a new Concert payment under an existing booking, then create it at PayMock. */
function createConcertPayment(PDO $db, array $booking, array $input): array
{
    $method = $input['method'] ?? 'promptpay';
    $createdAt = date('Y-m-d H:i:s');
    $db->prepare('INSERT INTO concert_payments (booking_id, method, status, created_at) VALUES (?, ?, ?, ?)')
        ->execute([$booking['id'], $method, 'PENDING', $createdAt]);
    // This is a numeric ID of the Concert payment record, not the booking ID.
    // A failed booking can therefore create payment 74, 75, ... safely.
    $concertPaymentId = (string) $db->lastInsertId();

    $callbackUrl = getenv('MY_WEBHOOK_URL') ?: 'http://concert_backend:5000/api/webhook';
    $postData = [
        'payment_id'   => $concertPaymentId,
        'amount'       => (float) $booking['amount'],
        'method'       => $method,
        'callback_url' => $callbackUrl,
        'expires_in'   => 600,
    ];

    if ($method === 'card') {
        $postData['card_number'] = $input['card_number'] ?? '4242424242424242';
        $postData['exp_month']   = $input['exp_month'] ?? '12';
        $postData['exp_year']    = $input['exp_year'] ?? '28';
        $postData['cvc']         = $input['cvc'] ?? '123';
    }

    $paymockApiUrl = getenv('PAYMOCK_URL') ?: 'http://paymock:80/v1/payments';
    $ch = curl_init($paymockApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer pm_test_key_concert_system_2024',
        'Content-Type: application/json',
        'Idempotency-Key: ' . $concertPaymentId,
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $rawResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $gateway = json_decode($rawResponse ?: '', true) ?? [];
    if ($httpCode !== 201 || empty($gateway['id'])) {
        $db->prepare('UPDATE concert_payments SET status = ?, failure_code = ? WHERE id = ?')
            ->execute(['GATEWAY_ERROR', 'gateway_request_failed', $concertPaymentId]);
        return [
            'ok' => false,
            'http_code' => $httpCode ?: 500,
            'concert_payment_id' => (int) $concertPaymentId,
            'raw' => $rawResponse,
        ];
    }

    $gatewayStatus = $gateway['status'] ?? 'PENDING';
    $providerId = $gateway['id'];
    $db->prepare('UPDATE concert_payments SET provider_payment_id = ?, status = ?, failure_code = ? WHERE id = ?')
        ->execute([$providerId, $gatewayStatus, $gateway['failure_code'] ?? null, $concertPaymentId]);
    $db->prepare('UPDATE bookings SET payment_id = ?, status = ?, paid_at = ? WHERE id = ?')
        ->execute([
            $providerId,
            bookingStatus($gatewayStatus),
            $gatewayStatus === 'COMPLETED' ? ($gateway['paid_at'] ?? $createdAt) : null,
            $booking['id'],
        ]);

    return [
        'ok' => true,
        'booking_id' => (int) $booking['id'],
        'concert_payment_id' => (int) $concertPaymentId,
        'payment_id' => $providerId,
        'status' => $gatewayStatus,
        'failure_code' => $gateway['failure_code'] ?? null,
        'method' => $gateway['method'] ?? $method,
        'qrcode_url' => browserUrl($gateway['qrcode_url'] ?? null),
        'bank_simulator_url' => browserUrl($gateway['bank_simulator_url'] ?? null),
    ];
}


// ─────────────────────────────────────────────────────────────
// 1. API: รับ Webhook จาก PayMock
// ─────────────────────────────────────────────────────────────
if (($uri === '/api/webhook' || $uri === '/webhooks/payment') && $method === 'POST') {
    header('Content-Type: application/json');

    $sigHeader = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    $rawBody   = file_get_contents('php://input');

    // แยก t=timestamp, v1=signature
    preg_match('/t=(\d+),v1=([a-f0-9]+)/', $sigHeader, $matches);
    $timestamp = (int)($matches[1] ?? 0);
    $signature = $matches[2] ?? '';

    // Webhook Secret ของระบบคอนเสิร์ต
    $secret   = 'whsec_concert_super_secret_2024';
    $expected = hash_hmac('sha256', "{$timestamp}.{$rawBody}", $secret);

    $sigMatch     = ($signature !== '' && hash_equals($expected, $signature));
    $timestampOk  = ($timestamp > 0 && abs(time() - $timestamp) <= 300);
    $isValid      = $sigMatch && $timestampOk;

    // ตรวจสอบว่าเป็น replay หรือ bad_sig
    $rejectionReason = null;
    if (!$isValid) {
        if (!$sigMatch) {
            $rejectionReason = 'bad_signature';   // signature ผิด (จงใจส่งผิด)
        } elseif (!$timestampOk) {
            $rejectionReason = 'replay_attack';   // timestamp เก่า > 5 นาที
        } else {
            $rejectionReason = 'unknown';
        }
    }

    // ดึง payload (parse ก่อน log ทุกกรณี)
    $payload    = json_decode($rawBody, true) ?? [];
    $paymentId  = $payload['id'] ?? null;
    $orderRef   = $payload['payment_id'] ?? null;
    $event      = $payload['event'] ?? 'unknown';
    $status     = $payload['status'] ?? 'PENDING';

    // บันทึก Log ทุกกรณี (ทั้ง valid และ invalid)
    $stmt = $db->prepare(
        "INSERT INTO webhook_logs (order_ref, payment_id, event, signature_valid, rejection_reason, response_code, payload, received_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $responseCode = $isValid ? 200 : 401;
    $stmt->execute([$orderRef, $paymentId, $event, $isValid ? 1 : 0, $rejectionReason, $responseCode, $rawBody, date('Y-m-d H:i:s')]);

    if (!$isValid) {
        // ปฏิเสธ webhook → ไม่อัปเดต booking
        http_response_code(401);
        echo json_encode(['error' => 'invalid_signature', 'reason' => $rejectionReason]);
        exit;
    }

    // ✅ Signature ถูกต้อง → อัปเดต Concert payment ก่อน
    if ($paymentId) {
        $paymentStmt = $db->prepare('SELECT * FROM concert_payments WHERE provider_payment_id = ?');
        $paymentStmt->execute([$paymentId]);
        $concertPayment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
        if ($concertPayment) {
            $db->prepare('UPDATE concert_payments SET status = ?, failure_code = ? WHERE id = ?')
                ->execute([$status, $payload['failure_code'] ?? null, $concertPayment['id']]);
            // A delayed webhook for an older Concert payment cannot overwrite
            // the booking after the user has created a newer payment.
            $stmtUpdate = $db->prepare('UPDATE bookings SET status = ?, paid_at = ? WHERE id = ? AND payment_id = ?');
            $stmtUpdate->execute([
                bookingStatus($status),
                $status === 'COMPLETED' ? ($payload['paid_at'] ?? date('Y-m-d H:i:s')) : null,
                $concertPayment['booking_id'], $paymentId,
            ]);
        }
    }

    echo json_encode(['ok' => true, 'message' => 'Webhook received and processed']);
    exit;
}


// ─────────────────────────────────────────────────────────────
// 2. API: สร้างการจองตั๋ว และยิงไปหา PayMock Gateway
// ─────────────────────────────────────────────────────────────
if ($uri === '/api/create-booking' && $method === 'POST') {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $seat = $input['seat'] ?? 'VIP-01';
    $amount = (float)($input['amount'] ?? 1800.00);
    $orderRef = 'TK-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

    // บันทึกลงระบบคอนเสิร์ต สถานะเริ่มต้น PENDING
    $stmt = $db->prepare("INSERT INTO bookings (order_ref, concert_title, seat, amount, status, created_at) VALUES (?, ?, ?, ?, 'PENDING', ?)");
    $stmt->execute([$orderRef, 'Coldplay Live in Bangkok 2026', $seat, $amount, date('Y-m-d H:i:s')]);
    $bookingId = (int) $db->lastInsertId();
    $booking = $db->query('SELECT * FROM bookings WHERE id = ' . $bookingId)->fetch(PDO::FETCH_ASSOC);
    $result = createConcertPayment($db, $booking, $input);
    http_response_code($result['ok'] ? 200 : $result['http_code']);
    echo json_encode($result);
    exit;
}

// ─────────────────────────────────────────────────────────────
// 2.1 API: สร้าง payment ใหม่ใน booking เดิม
// ─────────────────────────────────────────────────────────────
if (preg_match('#^/api/bookings/(\d+)/payments$#', $uri, $matches) && $method === 'POST') {
    header('Content-Type: application/json');
    $stmt = $db->prepare('SELECT * FROM bookings WHERE id = ?');
    $stmt->execute([(int) $matches[1]]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$booking) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'booking_not_found']);
        exit;
    }
    if ($booking['status'] === 'PAID') {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'booking_already_paid']);
        exit;
    }
    if (!in_array($booking['status'], ['FAILED', 'EXPIRED'], true)) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'booking_payment_not_retryable']);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $result = createConcertPayment($db, $booking, $input);
    http_response_code($result['ok'] ? 200 : $result['http_code']);
    echo json_encode($result);
    exit;
}

// ─────────────────────────────────────────────────────────────
// 2.1 API: Reconcile ตรวจสอบสถานะกับ PayMock Gateway (แก้ Webhook Lost)
// ─────────────────────────────────────────────────────────────
if ($uri === '/api/reconcile-booking' && $method === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $paymentId = $input['payment_id'] ?? '';

    $stmt = $db->prepare("SELECT * FROM bookings WHERE payment_id = ?");
    $stmt->execute([$paymentId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking || empty($booking['payment_id'])) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'booking_or_payment_not_found']);
        exit;
    }

    $paymockBase = getenv('PAYMOCK_URL') ? str_replace('/v1/payments', '', getenv('PAYMOCK_URL')) : 'http://paymock:80';
    $queryUrl = $paymockBase . '/v1/payments/' . $paymentId;

    $ch = curl_init($queryUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer pm_test_key_concert_system_2024',
        'Accept: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $gwResponse = curl_exec($ch);
    $gwCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $gwData = json_decode($gwResponse, true) ?? [];
    $gwStatus = $gwData['status'] ?? 'PENDING';
    $paidAt   = $gwData['paid_at'] ?? null;

    if ($gwCode === 200) {
        $newBookingStatus = match($gwStatus) {
            'COMPLETED' => 'PAID',
            'FAILED'    => 'FAILED',
            'EXPIRED'   => 'EXPIRED',
            default     => $booking['status']
        };

        // Reconciliation targets the one PayMock transaction stored on this booking.
        $db->prepare('UPDATE concert_payments SET status = ?, failure_code = ? WHERE provider_payment_id = ?')
            ->execute([$gwStatus, $gwData['failure_code'] ?? null, $paymentId]);
        $stmtUp = $db->prepare("UPDATE bookings SET status = ?, paid_at = ? WHERE payment_id = ?");
        $stmtUp->execute([
            $newBookingStatus,
            $gwStatus === 'COMPLETED' ? ($paidAt ?: date('Y-m-d H:i:s')) : null,
            $paymentId,
        ]);

        echo json_encode([
            'ok'             => true,
            'reconciled'     => true,
            'gateway_status' => $gwStatus,
            'booking_status' => $newBookingStatus,
            'payment_id'     => $paymentId,
        ]);
    } else {
        echo json_encode([
            'ok'       => false,
            'error'    => 'reconcile_failed',
            'httpCode' => $gwCode,
            'raw'      => $gwResponse
        ]);
    }
    exit;
}

// ─────────────────────────────────────────────────────────────
// 2.2 API: ยกเลิกการจอง (เรียก POST /v1/payments/{id}/cancel)
// ─────────────────────────────────────────────────────────────
if ($uri === '/api/cancel-booking' && $method === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $paymentId = $input['payment_id'] ?? '';

    $stmt = $db->prepare("SELECT * FROM bookings WHERE payment_id = ?");
    $stmt->execute([$paymentId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking || empty($booking['payment_id'])) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'booking_or_payment_not_found']);
        exit;
    }

    $paymockBase = getenv('PAYMOCK_URL') ? str_replace('/v1/payments', '', getenv('PAYMOCK_URL')) : 'http://paymock:80';
    $cancelUrl = $paymockBase . '/v1/payments/' . $paymentId . '/cancel';

    $ch = curl_init($cancelUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer pm_test_key_concert_system_2024',
        'Accept: application/json',
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $gwResponse = curl_exec($ch);
    $gwCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $gwData = json_decode($gwResponse, true) ?? [];

    if ($gwCode === 200 && ($gwData['ok'] ?? false)) {
        $db->prepare("UPDATE concert_payments SET status = 'FAILED', failure_code = ? WHERE provider_payment_id = ?")
            ->execute([$gwData['failure_code'] ?? 'cancelled', $paymentId]);
        $stmtUp = $db->prepare("UPDATE bookings SET status = 'FAILED' WHERE payment_id = ?");
        $stmtUp->execute([$paymentId]);
        echo json_encode([
            'ok'             => true,
            'payment_id'     => $paymentId,
            'booking_status' => 'FAILED',
            'failure_code'   => $gwData['failure_code'] ?? 'cancelled',
        ]);
    } else {
        http_response_code($gwCode ?: 409);
        echo json_encode([
            'ok'       => false,
            'error'    => 'cannot_cancel',
            'httpCode' => $gwCode,
            'raw'      => $gwData ?: $gwResponse,
        ]);
    }
    exit;
}

// ─────────────────────────────────────────────────────────────
// 3. API: ดึงสถานะ Booking ปัจจุบัน & Webhook Logs
// ─────────────────────────────────────────────────────────────
if ($uri === '/api/booking-status' && $method === 'GET') {
    header('Content-Type: application/json');
    $paymentId = $_GET['payment_id'] ?? '';
    $stmt = $db->prepare("SELECT * FROM bookings WHERE payment_id = ?");
    $stmt->execute([$paymentId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt2 = $db->prepare("SELECT * FROM webhook_logs WHERE payment_id = ? ORDER BY id DESC LIMIT 5");
    $stmt2->execute([$paymentId]);
    $logs = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $payments = [];
    if ($booking) {
        $paymentStmt = $db->prepare('SELECT id, provider_payment_id, method, status, failure_code, created_at FROM concert_payments WHERE booking_id = ? ORDER BY id DESC');
        $paymentStmt->execute([$booking['id']]);
        $payments = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'booking'  => $booking ?: null,
        'payments' => $payments,
        'logs'     => $logs
    ]);
    exit;
}

// ─────────────────────────────────────────────────────────────
// 4. FRONTEND: หน้าเว็บคอนเสิร์ต (HTML + Tailwind + Modal)
// ─────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coldplay Live in Bangkok 2026 — Concert Ticket</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Prompt', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">

    <!-- Top Navigation -->
    <header class="border-b border-slate-800 bg-slate-900/60 backdrop-blur sticky top-0 z-40">
        <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-2xl">🎫</span>
                <div>
                    <div class="font-black text-lg text-white tracking-wide">THAI TICKET CONCERT</div>
                    <div class="text-[10px] text-amber-400 font-mono">Running on http://localhost:5000 (Concert Backend)</div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="http://localhost:8081/admin" target="_blank" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-3 py-1.5 rounded-lg border border-slate-700 transition">
                    ⚙️ PayMock Admin (8081) ↗
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-6xl mx-auto px-4 py-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left 2 Cols: Concert Booking Card -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-2xl">
                <!-- Hero Image Banner -->
                <div class="relative h-56 bg-gradient-to-r from-amber-600 via-rose-600 to-indigo-800 p-6 flex flex-col justify-end text-white">
                    <span class="bg-black/40 backdrop-blur text-amber-300 text-xs font-bold px-2.5 py-1 rounded-full w-max mb-2">
                        ⭐ WORLD TOUR 2026
                    </span>
                    <h1 class="text-3xl font-extrabold tracking-tight">COLDPLAY: MUSIC OF THE SPHERES</h1>
                    <p class="text-slate-200 text-sm mt-1">📍 Rajamangala National Stadium, Bangkok · 28 พ.ย. 2026</p>
                </div>

                <!-- Booking Options Form -->
                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">เลือกโซนที่นั่ง</label>
                        <div class="grid grid-cols-3 gap-3">
                            <label class="cursor-pointer border border-slate-700 hover:border-amber-500 rounded-xl p-3 flex flex-col justify-between has-[:checked]:border-amber-500 has-[:checked]:bg-amber-500/10 transition">
                                <input type="radio" name="seat" value="VIP-A1" class="hidden" checked onchange="updatePrice('VIP-A1', 3500)">
                                <span class="font-bold text-sm">Zone VIP (Standing)</span>
                                <span class="text-amber-400 font-bold mt-2">฿3,500</span>
                            </label>
                            <label class="cursor-pointer border border-slate-700 hover:border-amber-500 rounded-xl p-3 flex flex-col justify-between has-[:checked]:border-amber-500 has-[:checked]:bg-amber-500/10 transition">
                                <input type="radio" name="seat" value="Zone-B" class="hidden" onchange="updatePrice('Zone-B', 2200)">
                                <span class="font-bold text-sm">Zone B (Seated)</span>
                                <span class="text-amber-400 font-bold mt-2">฿2,200</span>
                            </label>
                            <label class="cursor-pointer border border-slate-700 hover:border-amber-500 rounded-xl p-3 flex flex-col justify-between has-[:checked]:border-amber-500 has-[:checked]:bg-amber-500/10 transition">
                                <input type="radio" name="seat" value="Zone-C" class="hidden" onchange="updatePrice('Zone-C', 1500)">
                                <span class="font-bold text-sm">Zone C (Standard)</span>
                                <span class="text-amber-400 font-bold mt-2">฿1,500</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">ช่องทางชำระเงิน</label>
                        <select id="paymentMethod" onchange="toggleCardInputs()" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white">
                            <option value="promptpay">📱 PromptPay QR Code (Native Modal)</option>
                            <option value="card">💳 บัตรเครดิต / เดบิต (Direct API)</option>
                        </select>
                        <p class="mt-2 text-[11px] text-slate-500">
                            Chaos / Webhook ผิดปกติตั้งที่
                            <a href="http://localhost:8081/admin" target="_blank" class="text-amber-400 hover:underline">PayMock Admin</a>
                            ไม่ส่ง scenario จากหน้านี้
                        </p>
                    </div>

                    <!-- Direct Card Form (Shown only if method === 'card') -->
                    <div id="cardInputSection" style="display: none;" class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-300">กรอกข้อมูลบัตรเครดิต (Direct API ไม่ผ่าน checkout_url)</span>
                            <span class="text-[10px] text-amber-400 font-mono">Magic Cards Enabled</span>
                        </div>
                        <div>
                            <label class="block text-[11px] text-slate-400 mb-1">หมายเลขบัตร (Card Number)</label>
                            <input type="text" id="cardNumber" value="4242424242424242" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm font-mono text-white tracking-widest focus:border-amber-500 outline-none">
                        </div>
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            <span class="text-[10px] text-slate-500 self-center mr-1">Magic Test:</span>
                            <button type="button" onclick="setCard('4242424242424242')" class="text-[10px] px-2 py-0.5 rounded bg-emerald-950/80 border border-emerald-500/40 text-emerald-300 hover:bg-emerald-900/60 transition cursor-pointer">✅ ผ่าน</button>
                            <button type="button" onclick="setCard('4000000000000002')" class="text-[10px] px-2 py-0.5 rounded bg-rose-950/80 border border-rose-500/40 text-rose-300 hover:bg-rose-900/60 transition cursor-pointer">❌ ระงับ</button>
                            <button type="button" onclick="setCard('4000000000009995')" class="text-[10px] px-2 py-0.5 rounded bg-rose-950/80 border border-rose-500/40 text-rose-300 hover:bg-rose-900/60 transition cursor-pointer">❌ วงเงินไม่พอ</button>
                            <button type="button" onclick="setCard('4000000000000069')" class="text-[10px] px-2 py-0.5 rounded bg-rose-950/80 border border-rose-500/40 text-rose-300 hover:bg-rose-900/60 transition cursor-pointer">❌ หมดอายุ</button>
                            <button type="button" onclick="setCard('4000000000000127')" class="text-[10px] px-2 py-0.5 rounded bg-rose-950/80 border border-rose-500/40 text-rose-300 hover:bg-rose-900/60 transition cursor-pointer">❌ CVC</button>
                            <button type="button" onclick="setCard('4000000000000119')" class="text-[10px] px-2 py-0.5 rounded bg-rose-950/80 border border-rose-500/40 text-rose-300 hover:bg-rose-900/60 transition cursor-pointer">❌ ประมวลผล</button>
                            <button type="button" onclick="setCard('4100000000000019')" class="text-[10px] px-2 py-0.5 rounded bg-rose-950/80 border border-rose-500/40 text-rose-300 hover:bg-rose-900/60 transition cursor-pointer">❌ ทุจริต</button>
                        </div>
                        <div class="grid grid-cols-2 gap-3 pt-1">
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">วันหมดอายุ (MM/YY)</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="text" id="expMonth" value="12" maxlength="2" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-1.5 text-xs font-mono text-center text-white">
                                    <input type="text" id="expYear" value="28" maxlength="2" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-1.5 text-xs font-mono text-center text-white">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">CVC</label>
                                <input type="text" id="cvc" value="123" maxlength="4" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-1.5 text-xs font-mono text-center text-white">
                            </div>
                        </div>
                    </div>

                    <!-- Price summary & Action button -->
                    <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
                        <div>
                            <div class="text-xs text-slate-400">ยอดรวมทั้งสิ้น</div>
                            <div id="priceDisplay" class="text-2xl font-black text-amber-400">฿3,500.00</div>
                        </div>
                        <button onclick="startBooking()" id="bookBtn" class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black px-6 py-3.5 rounded-xl transition shadow-lg flex items-center gap-2 cursor-pointer">
                            <span id="bookBtnText">⚡ จองตั๋ว & แสดง QR Code (Modal)</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Current Booking Status Card -->
            <div id="bookingResultCard" style="display: none;" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="text-sm font-bold text-white flex items-center gap-2">
                        <span>🎟️ Payment ID:</span>
                        <span id="cardPaymentId" class="font-mono text-amber-400 text-xs">--</span>
                    </div>
                    <span id="cardStatusBadge" class="px-2.5 py-1 rounded-full text-xs font-bold bg-yellow-500/20 text-yellow-300 border border-yellow-500/30">
                        PENDING
                    </span>
                </div>
                <div class="grid grid-cols-3 gap-4 text-xs">
                    <div>
                        <div class="text-slate-500">ที่นั่ง:</div>
                        <div id="cardSeat" class="font-bold text-white text-sm mt-0.5">--</div>
                    </div>
                    <div>
                        <div class="text-slate-500">ยอดเงิน:</div>
                        <div id="cardAmount" class="font-bold text-white text-sm mt-0.5">--</div>
                    </div>
                    <div>
                        <div class="text-slate-500">เวลาออกตั๋ว:</div>
                        <div id="cardPaidAt" class="font-bold text-slate-400 text-sm mt-0.5">รอชำระเงิน...</div>
                    </div>
                </div>

                <div id="pendingActions" class="pt-3 border-t border-slate-800 flex items-center justify-between gap-3 flex-wrap">
                    <div class="text-[11px] text-slate-400">
                        รอ webhook หรือดึงสถานะจาก Gateway (GET) / ยกเลิกก่อนจ่าย
                    </div>
                    <div class="flex gap-2">
                        <button onclick="cancelBooking()" id="cancelBtn" class="bg-slate-700 hover:bg-slate-600 text-white font-bold text-xs px-3.5 py-1.5 rounded-lg transition shadow cursor-pointer">
                            ✕ ยกเลิกการจอง
                        </button>
                        <button onclick="reconcileBooking()" id="reconcileBtn" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs px-3.5 py-1.5 rounded-lg transition shadow cursor-pointer">
                            🔄 ดึงสถานะตรง (GET)
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Col: Real-time Webhook Inspector -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 flex flex-col justify-between font-mono text-xs">
            <div>
                <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-base">📡</span>
                        <span class="font-bold text-white">Webhook Inspector</span>
                    </div>
                    <span class="inline-flex items-center gap-1.5 text-[10px] text-emerald-400">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        POST /api/webhook
                    </span>
                </div>

                <div class="text-[11px] text-slate-400 mb-2">
                    ทุก Webhook ที่ PayMock ยิงเข้ามาจะถูกบันทึกและแสดงที่นี่ พร้อมผล <strong>HMAC-SHA256</strong> ว่าผ่านหรือถูกปฏิเสธ:
                </div>

                <div id="webhookLogsContainer" class="space-y-2 max-h-96 overflow-y-auto">
                    <div class="p-3 bg-slate-950/60 rounded-xl border border-slate-800/80 text-slate-500 italic text-[11px]">
                        ยังไม่มี Webhook เข้ามา (กดจองตั๋วเพื่อทดสอบ)
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-800 text-[10px] text-slate-500 mt-4 space-y-1">
                <div>• Secret: <code class="text-slate-400">whsec_concert_super_secret_2024</code></div>
                <div>• Signature check: <span class="text-emerald-400">hash_equals() enabled</span></div>
            </div>
        </div>

    </div>

    <!-- ─────────────────────────────────────────────────────────────
         MODAL POPUP CONTAINER (Native PromptPay QR Modal - No iframe)
         ───────────────────────────────────────────────────────────── -->
    <div id="paymentModal" style="display:none; position:fixed; inset:0; background:rgba(2,6,23,0.85); z-index:9999; justify-content:center; align-items:center;" class="backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl overflow-hidden w-full max-w-md shadow-2xl border border-slate-100 flex flex-col">
            <!-- Modal Header -->
            <div class="flex justify-between items-center px-6 py-4 bg-slate-900 text-white">
                <div class="flex items-center gap-2.5">
                    <span class="text-xl">📱</span>
                    <div>
                        <div class="text-sm font-bold tracking-tight">PromptPay QR Code</div>
                        <div class="text-[10px] text-emerald-400 font-mono">Native QR (Direct API)</div>
                    </div>
                </div>
                <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold px-2 py-0.5 rounded cursor-pointer transition">
                    ✕
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 text-center space-y-4">
                <div class="flex items-center justify-between bg-slate-50 border border-slate-200/80 rounded-2xl p-3 px-4">
                    <div class="text-left">
                        <div class="text-[11px] text-slate-500 font-medium">Payment ID</div>
                        <div id="modalPaymentId" class="font-mono font-bold text-slate-800 text-[11px] mt-0.5 break-all">txn_…</div>
                    </div>
                    <div class="text-right">
                        <div class="text-[11px] text-slate-500 font-medium">ยอดชำระ</div>
                        <div id="modalAmount" class="font-black text-emerald-600 text-lg">฿3,500.00</div>
                    </div>
                </div>

                <!-- QR Code Display -->
                <div class="bg-white p-4 rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center shadow-inner relative">
                    <img id="modalQrImg" src="" alt="PromptPay QR Code" class="w-60 h-60 object-contain rounded-lg">
                    <div class="mt-2 text-[11px] text-slate-400 font-medium flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-ping"></span>
                        <span>สแกนด้วยกล้องมือถือ หรือคลิกปุ่มจำลองด้านล่าง</span>
                    </div>
                </div>

                <!-- Action Button: Open Mock Mobile Banking -->
                <div class="space-y-2">
                    <a id="bankSimulateLink" href="#" target="_blank" class="w-full bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-emerald-600/20 flex items-center justify-center gap-2 text-sm transition">
                        <span>📲 เปิดหน้าจำลอง Mobile Banking (โอนเงิน) ↗</span>
                    </a>
                    <p class="text-[11px] text-slate-400 leading-relaxed text-left bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                        💡 <strong>Direct QR Flow:</strong> ระบบนี้ไม่มี <code class="text-rose-500 bg-rose-50 px-1 rounded">checkout_url</code> หน้าเว็บคอนเสิร์ตดึงรูปจาก <code class="text-emerald-600 bg-emerald-50 px-1 rounded">qrcode_url</code> มาแสดงใน Modal โดยตรง เมื่อกดยืนยันโอนเงินในหน้าจำลองแล้ว PayMock จะยิง Webhook มาปิด Modal อัตโนมัติ
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Handling -->
    <script>
        let currentSeat = 'VIP-A1';
        let currentAmount = 3500;
        let currentPaymentId = null;
        let currentBookingId = null;
        let currentBookingStatus = null;
        let pollingTimer = null;

        function updatePrice(seat, amount) {
            currentSeat = seat;
            currentAmount = amount;
            document.getElementById('priceDisplay').innerText = '฿' + Number(amount).toLocaleString('th-TH', {minimumFractionDigits: 2});
        }

        function toggleCardInputs() {
            const method = document.getElementById('paymentMethod').value;
            const cardSection = document.getElementById('cardInputSection');
            const bookBtnText = document.getElementById('bookBtnText');
            const canCreatePayment = currentBookingId && ['FAILED', 'EXPIRED'].includes(currentBookingStatus);
            if (canCreatePayment) {
                bookBtnText.innerText = '💳 สร้าง Payment ใหม่ใน Booking เดิม';
            } else if (method === 'card') {
                cardSection.style.display = 'block';
                bookBtnText.innerText = '⚡ ชำระเงินด้วยบัตรทันที (Direct API)';
            } else {
                cardSection.style.display = 'none';
                bookBtnText.innerText = '⚡ จองตั๋ว & แสดง QR Code (Modal)';
            }
        }

        function setCard(num) {
            document.getElementById('cardNumber').value = num;
        }

        async function startBooking() {
            const btn = document.getElementById('bookBtn');
            btn.disabled = true;
            btn.classList.add('opacity-70');

            const method = document.getElementById('paymentMethod').value;

            const payload = {
                seat: currentSeat,
                amount: currentAmount,
                method: method
            };

            if (method === 'card') {
                payload.card_number = document.getElementById('cardNumber').value.trim();
                payload.exp_month = document.getElementById('expMonth').value.trim();
                payload.exp_year = document.getElementById('expYear').value.trim();
                payload.cvc = document.getElementById('cvc').value.trim();
            }

            try {
                const createPaymentForBooking = currentBookingId && ['FAILED', 'EXPIRED'].includes(currentBookingStatus);
                const endpoint = createPaymentForBooking
                    ? '/api/bookings/' + encodeURIComponent(currentBookingId) + '/payments'
                    : '/api/create-booking';
                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.ok) {
                    currentPaymentId = data.payment_id;
                    currentBookingId = data.booking_id;
                    currentBookingStatus = data.status;
                    showBookingCard(data.payment_id, currentSeat, currentAmount, data.status);

                    if (method === 'promptpay' && data.qrcode_url) {
                        openPromptPayModal(data.payment_id, currentAmount, data.qrcode_url, data.bank_simulator_url);
                        startPollingStatus(data.payment_id);
                    } else if (method === 'card') {
                        if (data.status === 'COMPLETED') {
                            updateBadge('PAID');
                        } else if (data.status === 'FAILED') {
                            updateBadge('FAILED', data.failure_code);
                            alert('❌ ชำระเงินผ่านบัตรไม่สำเร็จ: ' + (data.failure_code || 'declined'));
                        }
                        startPollingStatus(data.payment_id);
                    }
                } else {
                    alert('เกิดข้อผิดพลาดในการสร้างรายการ: ' + JSON.stringify(data));
                }
            } catch (err) {
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ Backend: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.classList.remove('opacity-70');
            }
        }

        function openPromptPayModal(paymentId, amount, qrUrl, simulateUrl) {
            document.getElementById('modalPaymentId').innerText = paymentId;
            document.getElementById('modalAmount').innerText = '฿' + Number(amount).toLocaleString('th-TH', {minimumFractionDigits: 2});
            document.getElementById('modalQrImg').src = qrUrl;
            document.getElementById('bankSimulateLink').href = simulateUrl || '#';
            document.getElementById('paymentModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }

        function showBookingCard(paymentId, seat, amount, status) {
            document.getElementById('bookingResultCard').style.display = 'block';
            document.getElementById('cardPaymentId').innerText = paymentId;
            document.getElementById('cardSeat').innerText = seat;
            document.getElementById('cardAmount').innerText = '฿' + Number(amount).toLocaleString('th-TH');
            updateBadge(status);
        }

        function updateBadge(status, reason) {
            const badge = document.getElementById('cardStatusBadge');
            const recSection = document.getElementById('pendingActions');
            if (status === 'PAID' || status === 'COMPLETED') {
                badge.className = 'px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30';
                badge.innerText = '✅ PAID (ออกตั๋วสำเร็จ)';
                document.getElementById('cardPaidAt').innerText = new Date().toLocaleTimeString();
                if (recSection) recSection.style.display = 'none';
            } else if (status === 'FAILED') {
                badge.className = 'px-2.5 py-1 rounded-full text-xs font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30';
                badge.innerText = '❌ FAILED' + (reason ? ` (${reason})` : '');
                if (recSection) recSection.style.display = 'none';
                document.getElementById('bookBtnText').innerText = '💳 สร้าง Payment ใหม่ใน Booking เดิม';
            } else if (status === 'REJECTED') {
                badge.className = 'px-2.5 py-1 rounded-full text-xs font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30';
                badge.innerText = '🔒 REJECTED (Signature ปลอม - ปฏิเสธออกตั๋ว)';
                if (recSection) recSection.style.display = 'none';
            } else {
                badge.className = 'px-2.5 py-1 rounded-full text-xs font-bold bg-yellow-500/20 text-yellow-300 border border-yellow-500/30';
                badge.innerText = '⏳ PENDING (รอชำระเงิน / รอ Webhook)';
                if (recSection) recSection.style.display = 'flex';
                toggleCardInputs();
            }
        }

        function startPollingStatus(ref) {
            if (pollingTimer) clearInterval(pollingTimer);
            pollingTimer = setInterval(() => checkStatus(ref), 1500);
        }

        async function checkStatus(ref) {
            try {
                const res = await fetch('/api/booking-status?payment_id=' + encodeURIComponent(ref));
                const data = await res.json();

                if (data.booking) {
                    currentBookingId = data.booking.id;
                    currentBookingStatus = data.booking.status;
                    const hasRejected = data.logs && data.logs.some(l => parseInt(l.signature_valid) === 0);
                    if (data.booking.status === 'PAID') {
                        updateBadge('PAID');
                        closeModal();
                        clearInterval(pollingTimer);
                    } else if (data.booking.status === 'FAILED') {
                        updateBadge('FAILED');
                        clearInterval(pollingTimer);
                    } else if (hasRejected) {
                        updateBadge('REJECTED');
                    } else {
                        updateBadge(data.booking.status);
                    }
                }

                if (data.logs && data.logs.length > 0) {
                    renderWebhookLogs(data.logs);
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function cancelBooking() {
            if (!currentPaymentId) return;
            const btn = document.getElementById('cancelBtn');
            btn.disabled = true;
            btn.innerText = '⏳ กำลังยกเลิก...';
            try {
                const res = await fetch('/api/cancel-booking', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ payment_id: currentPaymentId })
                });
                const data = await res.json();
                if (data.ok) {
                    currentBookingStatus = 'FAILED';
                    updateBadge('FAILED', data.failure_code || 'cancelled');
                    closeModal();
                    if (pollingTimer) clearInterval(pollingTimer);
                } else {
                    alert('ยกเลิกไม่สำเร็จ: ' + JSON.stringify(data));
                }
            } catch (err) {
                alert('เกิดข้อผิดพลาดในการยกเลิก: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerText = '✕ ยกเลิกการจอง';
            }
        }

        async function reconcileBooking() {
            if (!currentPaymentId) return;
            const btn = document.getElementById('reconcileBtn');
            btn.disabled = true;
            btn.innerText = '⏳ กำลังตรวจสอบกับ Gateway...';
            try {
                const res = await fetch('/api/reconcile-booking', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ payment_id: currentPaymentId })
                });
                const data = await res.json();
                if (data.ok && data.booking_status === 'PAID') {
                    updateBadge('PAID');
                    document.getElementById('cardPaidAt').innerText = new Date().toLocaleTimeString() + ' (Reconciled)';
                    closeModal();
                    if (pollingTimer) clearInterval(pollingTimer);
                    alert('✅ Reconcile สำเร็จ! Gateway ยืนยันสถานะ COMPLETED -> ออกตั๋วเรียบร้อย');
                } else {
                    if (data.ok && ['FAILED', 'EXPIRED'].includes(data.booking_status)) {
                        currentBookingStatus = data.booking_status;
                        updateBadge(data.booking_status, data.failure_code);
                    }
                    alert('ผลการ Reconcile: ' + JSON.stringify(data));
                }
            } catch (err) {
                alert('เกิดข้อผิดพลาดในการ Reconcile: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerText = '🔄 ดึงสถานะตรง (Reconcile API)';
            }
        }

        function renderWebhookLogs(logs) {
            const c = document.getElementById('webhookLogsContainer');
            if (!logs || logs.length === 0) return;
            c.innerHTML = logs.map(l => {
                const valid = parseInt(l.signature_valid) === 1;
                const reason = l.rejection_reason || null;
                const code   = l.response_code || (valid ? 200 : 401);

                // ─── Badge ของ event ───
                let eventColor = 'text-emerald-400';
                let eventIcon  = '✅';
                if (l.event === 'payment.failed')  { eventColor = 'text-rose-400';   eventIcon = '❌'; }
                if (l.event === 'payment.expired') { eventColor = 'text-yellow-400'; eventIcon = '⏱️'; }

                // ─── Badge ของ sig ───
                let sigBadge = '';
                let sigBg    = '';
                if (valid) {
                    sigBadge = '✅ Signature Valid';
                    sigBg    = 'border-emerald-500/30 bg-emerald-950/40';
                } else if (reason === 'replay_attack') {
                    sigBadge = '⛔ REJECTED — Replay Attack (timestamp เก่าเกิน 5 นาที)';
                    sigBg    = 'border-orange-500/40 bg-orange-950/30';
                } else if (reason === 'bad_signature') {
                    sigBadge = '🔒 REJECTED — Bad Signature (ลายเซ็นไม่ตรง)';
                    sigBg    = 'border-red-500/40 bg-red-950/30';
                } else {
                    sigBadge = '❓ REJECTED — Unknown';
                    sigBg    = 'border-gray-500/40 bg-gray-950/30';
                }

                // ─── HTTP Response code badge ───
                const codeBg   = code === 200 ? 'bg-emerald-700 text-white' : 'bg-red-700 text-white';

                // ─── ข้อมูล payload ───
                let payloadObj = {};
                try { payloadObj = JSON.parse(l.payload || '{}'); } catch(e) {}
                const payloadStatus = payloadObj.status || '-';
                const failureCode   = payloadObj.failure_code || '';

                return `
                <div class="p-3 rounded-xl border ${sigBg} font-mono text-[11px] space-y-1.5">
                    <div class="flex justify-between items-center">
                        <span class="${eventColor} font-bold text-xs">${eventIcon} ${l.event}</span>
                        <div class="flex items-center gap-2">
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold ${codeBg}">HTTP ${code}</span>
                            <span class="text-slate-500">${l.received_at}</span>
                        </div>
                    </div>
                    <div class="text-[10px] text-slate-400">
                        Payment: <span class="text-slate-300">${l.payment_id || payloadObj.id || '-'}</span>
                        · Status in payload: <span class="font-bold ${payloadStatus === 'COMPLETED' ? 'text-emerald-400' : payloadStatus === 'FAILED' ? 'text-rose-400' : 'text-yellow-400'}">${payloadStatus}${failureCode ? ' (' + failureCode + ')' : ''}</span>
                    </div>
                    <div class="text-[10px] font-semibold ${valid ? 'text-emerald-400' : 'text-red-400'}">
                        HMAC: ${sigBadge}
                    </div>
                    ${!valid ? `<div class="text-[10px] text-slate-500 italic">⚠️ Booking ไม่ถูกอัปเดต เนื่องจาก Webhook ถูกปฏิเสธ</div>` : ''}
                </div>`;
            }).join('');
        }

    </script>
</body>
</html>
