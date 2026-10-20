# PayMock – Mock Payment Gateway (Server แยก)

Payment Gateway จำลองสำหรับระบบจองตั๋วคอนเสิร์ต ออกแบบเป็น **ระบบภายนอกจริงๆ (External System)**
- คนละ codebase (`paymock/`)
- คนละ Database (SQLite ไฟล์เดียว)
- คนละ Port (`8081`)
- คุยกับระบบหลักผ่าน **HTTP REST API** และ **Webhook** พร้อม HMAC-SHA256 ลายเซ็นดิจิทัล

---

## 🚀 เริ่มต้นใช้งานอย่างรวดเร็ว (Docker)

```bash
cd paymock
docker compose up -d --build
```

หรือรันตรงด้วย PHP:

```bash
cd paymock
composer install
cp .env.example .env
php artisan key:generate
touch database/paymock.sqlite
php artisan migrate
php artisan serve --port=8081
# ในอีก terminal:
php artisan queue:work
# ในอีก terminal:
php artisan schedule:work
```

---

## 🌐 URLs & Ports

| ส่วน | URL | รายละเอียด |
|---|---|---|
| **API Base** | `http://localhost:8081/v1` | ใช้คุยกับระบบคอนเสิร์ต |
| **Hosted Card Checkout** | `http://localhost:8081/checkout/{public_id}` | หน้ากรอกบัตรของ PayMock แล้ว redirect กลับ `return_url` |
| **Admin Panel** | `http://localhost:8081/admin` | ชั้น 1 Chaos + ชั้น 2 Webhook (global), ดูรายการ, ส่ง Webhook ซ้ำ |
| **Health Check** | `http://localhost:8081/up` | Docker healthcheck |

*รหัสผ่าน Admin Panel ค่าเริ่มต้น:* `admin1234`

---

## 🔑 Authentication & Security

- **API Requests:** ส่ง Bearer token ใน Header:
  ```http
  Authorization: Bearer pm_test_key_concert_system_2024
  ```
- **Webhook Signature:** ทุก Webhook ส่งพร้อม Header:
  ```http
  X-Signature: t=1728812345,v1=abcdef...
  X-Event-Id: evt_01h...
  ```
  โดยลายเซ็นเกิดจาก `hash_hmac('sha256', "$timestamp.$body", $secret)`

---

## 📡 API Reference

### 1. สร้างรายการชำระเงิน
```http
POST /v1/payments
Authorization: Bearer {API_KEY}
Idempotency-Key: 12
Content-Type: application/json

{
  "payment_id": "12",
  "amount": 1500.00,
  "currency": "THB",
  "method": "card",           // "card" หรือ "promptpay"
  "callback_url": "http://your-concert-domain/webhooks/payment",
  "return_url": "http://your-concert-domain/payment/return",
  "expires_in": 600           // วินาที (default 600)
}
```

**Response (201 Created):**
```json
{
  "id": "txn_01h...",
  "payment_id": "12",
  "amount": 1500.00,
  "currency": "THB",
  "method": "card",
  "status": "PENDING",
  "failure_code": null,
  "checkout_url": "http://localhost:8081/checkout/txn_01h...",
  "return_url": "http://your-concert-domain/payment/return",
  "expires_at": "2024-10-13T12:00:00Z",
  "paid_at": null,
  "created_at": "2024-10-13T11:50:00Z"
}
```

> **Idempotency:** Merchant ทั่วไปใช้เลขเดียวกันได้ เช่น `payment_id: "12"` และ `Idempotency-Key: 12`, แล้วส่งซ้ำเฉพาะ retry หลัง timeout. Concert backend สร้าง Concert payment ก่อนเรียก PayMock และใช้เลขนั้นเป็นทั้ง `payment_id`/key; เมื่อ FAILED/EXPIRED จึงสร้าง Concert payment ใหม่ใต้ booking เดิม. ข้อจำกัดปัจจุบัน: timeout ระหว่าง Concert → PayMock ยังไม่ resend อัตโนมัติ จึงต้อง retry/reconcile attempt เดิมด้วย key เดิมก่อนสร้าง attempt ใหม่.

> **Identifier:** `id` (`txn_...`) คือ provider transaction ID เพียงค่าเดียวสำหรับ GET/cancel/QR/polling; `payment_id` คือเลขอ้างอิงของ merchant. ไม่มี `provider_transaction_id` เพราะซ้ำกับ `id`.

> **Hosted card flow:** Card payment สร้างเป็น `PENDING` ก่อน แล้วให้ browser redirect ไปยัง `checkout_url` (`/checkout/{public_id}`). ผู้ใช้กรอกบัตรในหน้า PayMock และฟอร์มจะ `POST /payments/{public_id}` เพื่อ settle payment เดิม จากนั้น Gateway redirect กลับ `return_url` โดยไม่เติม payment ID ลงใน URL. ส่ง `booking_ref` เป็น booking code เพื่อแสดงบนหน้า checkout/receipt และใช้สร้าง return path เช่น `/booking/{booking_ref}`; ระบบร้านค้าควรอ่านสถานะจากฐานข้อมูลหรือ webhook โดยใช้ mapping ที่บันทึกไว้. Demo ใช้ return path `/demo/bookings/{public_id}` ซึ่งอ่านสถานะล่าสุดจากฐานข้อมูลก่อนแสดง booking.

Public API ใช้ `booking_ref` เป็น booking code; request เก่าที่ยังส่ง `order_ref` รองรับเพื่อ backward compatibility แต่ response จะส่ง `booking_ref` เท่านั้น.

---

### 2. ตรวจสอบสถานะการชำระเงิน (Polling / Reconcile)
```http
GET /v1/payments/{payment_id}
Authorization: Bearer {API_KEY}
```

---

### 3. ยกเลิกรายการชำระเงินก่อนจ่าย
```http
POST /v1/payments/{payment_id}/cancel
Authorization: Bearer {API_KEY}
```

---

## 🧪 สถานการณ์จำลอง 4 หมวด (Test Plan)

### หมวด A: เลขบัตรวิเศษ (Magic Cards)
กรอกที่หน้า `/checkout/{id}`:

| เลขบัตร | ผลลัพธ์ | failure_code | หน้าที่ของระบบหลัก |
|---|---|---|---|
| `4242 4242 4242 4242` | ✅ สำเร็จ (COMPLETED) | - | ออกตั๋ว, seat → SOLD |
| `4000 0000 0000 0002` | ❌ ปฏิเสธทั่วไป | `card_declined` | แจ้งผู้ใช้ ให้ลองใหม่ถ้ายังอยู่ใน 10 นาที |
| `4000 0000 0000 9995` | ❌ วงเงินไม่พอ | `insufficient_funds` | เหมือนข้างบน |
| `4000 0000 0000 0069` | ❌ บัตรหมดอายุ | `expired_card` | เหมือนข้างบน |
| `4000 0000 0000 0127` | ❌ CVC ผิด | `incorrect_cvc` | เหมือนข้างบน |
| `4000 0000 0000 0119` | ⚠️ ระบบขัดข้อง | `processing_error` | ไม่ใช่ความผิดผู้ใช้ ให้ retry ได้ |
| `4000 0000 0000 3220` | 🔐 ต้อง OTP (3DS) | `3ds_required` | ค้าง PENDING ไม่ปลดล็อกที่นั่งทันที |
| `4100 0000 0000 0019` | 🚨 สงสัย Fraud | `fraud_suspected` | บันทึก `system_anomaly` (PAYMENT_FRAUD_CARD_TESTING) |
| `4000 0000 0000 0341` | ✅ สำเร็จ | - | ความผิดปกติของ Webhook ตั้งจาก Admin |

---

### หมวด B: PromptPay
- **ไม่จ่ายเลย:** ปล่อยจนเกิน `expires_at` → scheduler จะส่ง `payment.expired` → ระบบหลักยกเลิกจอง ปลดล็อกที่นั่ง
- **จ่ายหลังหมดเวลา (Late payment):** กดจ่ายหลังจากที่นั่งถูกปลดแล้ว → บันทึก `system_anomaly` (`LATE_PAYMENT`)
- **จ่ายซ้ำ 2 ครั้ง:** กดซ้ำบน QR เดิม → ออกตั๋วครั้งเดียว (Idempotency)

---

### หมวด C: Gateway / Network ผิดปกติ (ชั้น 1 — ตั้งที่ PayMock Admin)

ระบบคอนเสิร์ตไม่ส่ง `X-Mock-Scenario` — ตั้ง `chaos_mode` ที่ Admin แล้วทุกคำขอเข้า `/v1/*` และ checkout จะถูกจำลองตามนั้น

| Scenario | พฤติกรรมที่ลูกค้าเห็น |
|---|---|
| `down` | ตอบ `503` พร้อมข้อความ `Gateway มีปัญหา โปรดลองอีกครั้ง` |
| `slow:N` | หน่วง N วินาทีแล้วประมวลผลต่อ |
| `rate_limit` | API ตอบ `429` พร้อม `Retry-After: 30` |

ไม่มี timeout simulation, bad JSON, bad auth หรือสุ่ม 5xx ใน Chaos Mode

---

### หมวด D: Webhook ผิดปกติ (ชั้น 2 — ตั้งที่ PayMock Admin แบบ global)

| Scenario | ผลลัพธ์ | พฤติกรรมฝั่งระบบหลัก |
|---|---|---|
| `webhook_lost` | ไม่ส่ง Webhook เลย | ระบบหลักต้องมี Polling/Reconcile scheduler มารับ |
| `webhook_delay:N` | หน่วงเวลา N วินาทีก่อนส่ง | e.g. `webhook_delay:660` (11 นาที = เกินเวลาล็อก) |
| `webhook_dup:N` | ส่งซ้ำ N ครั้ง | e.g. `webhook_dup:3` ต้องประมวลผลครั้งเดียว |
| `webhook_bad_sig` | ส่ง Signature เป็น 0 หมด | ระบบหลักต้องตอบ 401 |
| `webhook_replay` | ส่ง timestamp เก่า 1 ชั่วโมง | ระบบหลักต้องปฏิเสธ (replay attack) |

---

## 🔗 ตัวอย่างโค้ดฝั่งระบบคอนเสิร์ต (Integration Snippets)

### 1. แนวทางเพิ่ม retry/reconcile หลัง Timeout
```php
// สร้าง Concert payment เพียงครั้งเดียวก่อนส่งไป PayMock
$concertPayment = ConcertPayment::create([
    'booking_id' => $booking->id,
    'method' => $method,
    'status' => 'PENDING',
]);
$merchantPaymentId = (string) $concertPayment->id;

try {
    $response = Http::withToken(config('services.paymock.key'))
        ->withHeaders([
            'Idempotency-Key' => $merchantPaymentId,
        ])
        ->timeout(8)
        ->retry(2, 500, throw: false)
        ->post(config('services.paymock.url') . '/v1/payments', [
            'payment_id'   => $merchantPaymentId,
            'amount'       => (float) $booking->b_total_amount,
            'method'       => $method, // 'card' or 'promptpay'
            'callback_url' => route('webhooks.payment'),
            'return_url'   => route('payment.return'),
        ]);

    $response->throw();
    return redirect($response->json('checkout_url'));

} catch (\Illuminate\Http\Client\ConnectionException|\Illuminate\Http\Client\RequestException $e) {
    // ห้ามสร้าง Concert payment ใหม่จาก timeout เพียงอย่างเดียว
    // enqueue retry/reconcile ของ $concertPayment โดยใช้ body และ key เดิม
    $concertPayment->update(['status' => 'GATEWAY_ERROR']);
    return back()->with('error', 'กำลังตรวจสอบสถานะการชำระเงิน');
}
```

> Snippet นี้เป็นแนวทางสำหรับการเพิ่ม retry/reconcile; ระบบ Concert ปัจจุบันยังไม่มี worker หรือ route สำหรับ resend แถวเดิมอัตโนมัติ และจะบันทึกเป็น `GATEWAY_ERROR` เมื่อไม่ได้ response.

### 2. Webhook Controller (ตรวจ Signature + จัดการสถานะ)
```php
public function __invoke(Request $request)
{
    // ตรวจ Timestamp & Format: t=123,v1=abc
    preg_match('/t=(\d+),v1=([a-f0-9]+)/', $request->header('X-Signature', ''), $matches);
    if (! $matches || abs(time() - (int) $matches[1]) > 300) {
        abort(400, 'Invalid or expired timestamp');
    }

    // ตรวจ HMAC Signature
    $expected = hash_hmac(
        'sha256',
        $matches[1] . '.' . $request->getContent(),
        config('services.paymock.webhook_secret')
    );
    if (! hash_equals($expected, $matches[2])) {
        abort(401, 'Invalid signature');
    }

    // Apply State (Idempotent ใน Database Transaction)
    $this->paymentStateService->apply(
        providerId: $request->input('id'),
        status:     $request->input('status'),
        amount:     (float) $request->input('amount')
    );

    return response()->json(['ok' => true]);
}
```

### 3. Reconcile Scheduler (ตามเก็บรายการตกหล่น)
```php
// app/Console/Commands/ReconcilePayments.php
Payment::where('p_status', 'PENDING')
    ->where('created_at', '<', now()->subMinute())
    ->each(function (Payment $payment) {
        $res = Http::withToken(config('services.paymock.key'))
            ->get(config('services.paymock.url') . '/v1/payments/' . $payment->p_provider_transaction_id);

        if ($res->successful()) {
            $this->paymentStateService->apply(
                $res->json('id'),
                $res->json('status'),
                (float) $res->json('amount')
            );
        }
    });
```
