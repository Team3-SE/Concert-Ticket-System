# คู่มือการเชื่อมต่อระบบคอนเสิร์ตกับ PayMock Gateway

เอกสารนี้รวบรวมโค้ดและคำสั่งที่ต้องนำไปใส่ใน **ระบบคอนเสิร์ตหลัก** เพื่อให้สามารถสื่อสารกับ PayMock ได้อย่างสมบูรณ์ รองรับทุกกรณีทดสอบ (Magic Cards, Chaos Mode, Webhook Lost/Dup/Delay)

---

## 1. อัปเดต CHECK Constraint ในฐานข้อมูลหลัก (MySQL)

ตาราง `system_anomaly` ต้องรองรับประเภทความผิดปกติเพิ่มเติม เช่น `LATE_PAYMENT`, `AMOUNT_MISMATCH`, และ `GATEWAY_UNREACHABLE`:

```sql
-- รันใน MySQL ของระบบคอนเสิร์ต
ALTER TABLE system_anomaly DROP CHECK chk_anomaly_type;
ALTER TABLE system_anomaly ADD CONSTRAINT chk_anomaly_type CHECK (sa_type IN
 ('PAYMENT_FRAUD_CARD_TESTING',
  'SEAT_HOARDING_ABUSE',
  'LATE_PAYMENT',
  'AMOUNT_MISMATCH',
  'GATEWAY_UNREACHABLE')
);
```

---

## 2. ตั้งค่าใน `.env` ของระบบคอนเสิร์ต

```dotenv
# PayMock Gateway Configuration
PAYMOCK_URL=http://localhost:8081
PAYMOCK_API_KEY=pm_test_key_concert_system_2024
PAYMOCK_WEBHOOK_SECRET=whsec_concert_super_secret_2024
PAYMOCK_SCENARIO=                  # ใส่ scenario เช่น webhook_delay:660 เพื่อทดสอบอัตโนมัติ
```

และเพิ่มใน `config/services.php`:

```php
'paymock' => [
    'url'            => env('PAYMOCK_URL', 'http://localhost:8081'),
    'key'            => env('PAYMOCK_API_KEY', 'pm_test_key_concert_system_2024'),
    'webhook_secret' => env('PAYMOCK_WEBHOOK_SECRET', 'whsec_concert_super_secret_2024'),
    'scenario'       => env('PAYMOCK_SCENARIO', null),
],
```

---

## 3. โค้ดฝั่งระบบคอนเสิร์ต

### 3.1 Service สร้างรายการชำระเงิน (`PaymockService.php`)
รองรับ timeout, retry, idempotency:

```php
<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymockService
{
    /**
     * ส่งคำขอสร้าง payment ไปยัง PayMock
     * ใช้ Idempotency-Key = booking_code เพื่อป้องกันการหักเงินซ้ำเมื่อเกิด retry
     */
    public function initiatePayment(Booking $booking, string $method = 'card'): string
    {
        $scenario = config('services.paymock.scenario');

        try {
            $headers = [
                'Idempotency-Key' => $booking->b_booking_code,
            ];
            if ($scenario) {
                $headers['X-Mock-Scenario'] = $scenario;
            }

            $response = Http::withToken(config('services.paymock.key'))
                ->withHeaders($headers)
                ->timeout(8)          // timeout 8 วินาที
                ->retry(2, 500, throw: false) // retry 2 ครั้ง ห่างกัน 500ms
                ->post(config('services.paymock.url') . '/v1/payments', [
                    'order_ref'    => $booking->b_booking_code,
                    'amount'       => (float) $booking->b_total_amount,
                    'currency'     => 'THB',
                    'method'       => $method, // 'card' หรือ 'promptpay'
                    'callback_url' => route('webhooks.paymock'),
                    'return_url'   => route('payment.return'),
                    'expires_in'   => 600, // 10 นาที
                ]);

            $response->throw();

            $data = $response->json();

            // บันทึก provider_transaction_id ลงในตาราง payment ของระบบหลัก
            $booking->payment()->updateOrCreate(
                ['booking_id' => $booking->b_booking_id],
                [
                    'p_provider_transaction_id' => $data['id'],
                    'p_amount'                  => $data['amount'],
                    'p_method'                  => $method,
                    'p_status'                  => 'PENDING',
                ]
            );

            // คืน checkout_url เพื่อ redirect ผู้ใช้ไปหน้าชำระเงิน
            return $data['checkout_url'];

        } catch (ConnectionException | RequestException $e) {
            Log::error('Gateway unreachable / timeout', [
                'booking' => $booking->b_booking_code,
                'error'   => $e->getMessage(),
            ]);

            // ปลดล็อกที่นั่ง และเปลี่ยนสถานะ booking เป็น FAILED
            $this->releaseSeatAndFailBooking($booking);

            throw new \RuntimeException('ระบบชำระเงินขัดข้องชั่วคราว กรุณาลองใหม่อีกครั้ง');
        }
    }

    private function releaseSeatAndFailBooking(Booking $booking): void
    {
        \DB::transaction(function () use ($booking) {
            $booking->update(['b_status' => 'FAILED']);
            $booking->seats()->update(['s_status' => 'AVAILABLE']);
            $booking->seatLockLogs()->update(['sll_status' => 'RELEASED', 'sll_released_at' => now()]);
        });
    }
}
```

---

### 3.2 Webhook Controller (`PaymockWebhookController.php`)
ตรวจ Signature HMAC-SHA256, ป้องกัน Replay Attack, และเรียกใช้ `PaymentStateService`:

```php
<?php

namespace App\Http\Controllers;

use App\Services\PaymentStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymockWebhookController extends Controller
{
    public function __construct(private readonly PaymentStateService $paymentState) {}

    public function __invoke(Request $request): JsonResponse
    {
        $rawContent = $request->getContent();
        $sigHeader  = $request->header('X-Signature', '');

        // 1. ตรวจสอบรูปแบบ: t={timestamp},v1={hash}
        if (! preg_match('/t=(\d+),v1=([a-f0-9]+)/', $sigHeader, $matches)) {
            abort(400, 'Invalid signature header format');
        }

        $timestamp = (int) $matches[1];
        $signature = $matches[2];

        // 2. ป้องกัน Replay Attack: ปฏิเสธถ้าอายุเกิน 5 นาที (300 วินาที)
        if (abs(time() - $timestamp) > 300) {
            abort(400, 'Webhook timestamp expired (replay attack detected)');
        }

        // 3. ตรวจสอบ HMAC-SHA256 ลายเซ็น
        $expectedSignature = hash_hmac(
            'sha256',
            "{$timestamp}.{$rawContent}",
            config('services.paymock.webhook_secret')
        );

        if (! hash_equals($expectedSignature, $signature)) {
            abort(401, 'Invalid webhook signature');
        }

        // 4. บันทึกและปรับปรุงสถานะ (Idempotent)
        $this->paymentState->apply(
            providerId:  $request->input('id'),
            status:      $request->input('status'),
            amount:      (float) $request->input('amount'),
            failureCode: $request->input('failure_code')
        );

        // ตอบ 200 เสมอเมื่อรับแล้ว เพื่อบอก gateway ว่าส่งสำเร็จ
        return response()->json(['ok' => true]);
    }
}
```

---

### 3.3 State Machine จัดการสถานะ (`PaymentStateService.php`)
เป็น **จุดเดียว** ที่เปลี่ยนสถานะของ Payment ทั้งจาก Webhook และ Polling:

```php
<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\SystemAnomaly;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentStateService
{
    public function apply(string $providerId, string $status, float $amount, ?string $failureCode = null): void
    {
        DB::transaction(function () use ($providerId, $status, $amount, $failureCode) {
            $payment = Payment::where('p_provider_transaction_id', $providerId)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                Log::warning("Payment not found for provider transaction: {$providerId}");
                return;
            }

            // ถ้าสถานะไม่ใช่ PENDING แล้ว (เคยประมวลผลไปแล้ว) ให้ข้าม (Idempotency)
            if ($payment->p_status !== 'PENDING') {
                return;
            }

            // ตรวจสอบยอดเงินตรงกันหรือไม่ (กรณีจ่ายยอดไม่ตรง)
            if (abs($amount - (float) $payment->p_amount) > 0.01) {
                SystemAnomaly::create([
                    'booking_id'        => $payment->booking_id,
                    'caused_by_user_id' => $payment->booking->customer_id,
                    'type'              => 'AMOUNT_MISMATCH',
                    'message'           => "Expected amount {$payment->p_amount}, received {$amount}",
                    'anomaly_code'      => 'ANOM_' . uniqid(),
                ]);
                return;
            }

            $booking = $payment->booking;

            if ($status === 'COMPLETED') {
                // ถ้า Booking ยัง PENDING_PAYMENT → ออกตั๋วและยืนยัน
                if ($booking->b_status === 'PENDING_PAYMENT') {
                    $payment->update(['p_status' => 'COMPLETED']);
                    $booking->update(['b_status' => 'CONFIRMED']);

                    // เปลี่ยนสถานะที่นั่งเป็น SOLD
                    $booking->seats()->update(['s_status' => 'SOLD']);

                    // ออก Order + Tickets
                    $this->issueOrderAndTickets($booking, $payment);

                } elseif ($booking->b_status === 'EXPIRED' || $booking->b_status === 'CANCELLED') {
                    // Late payment: จ่ายเงินสำเร็จหลังจากที่นั่งถูกปลดไปแล้ว
                    $payment->update(['p_status' => 'COMPLETED']);

                    SystemAnomaly::create([
                        'booking_id'        => $booking->b_booking_id,
                        'caused_by_user_id' => $booking->customer_id,
                        'type'              => 'LATE_PAYMENT',
                        'message'           => "Paid ฿{$amount} after booking was expired/cancelled",
                        'anomaly_code'      => 'ANOM_' . uniqid(),
                    ]);
                }

            } elseif (in_array($status, ['FAILED', 'EXPIRED'])) {
                // จ่ายไม่สำเร็จ หรือหมดเวลา
                $payment->update([
                    'p_status'       => $status,
                    'payment_metadata' => array_merge($payment->payment_metadata ?? [], ['failure_code' => $failureCode]),
                ]);

                // ปลดล็อกที่นั่ง
                $booking->update(['b_status' => $status]);
                $booking->seats()->update(['s_status' => 'AVAILABLE']);
                $booking->seatLockLogs()->update([
                    'sll_status'      => 'RELEASED',
                    'sll_released_at' => now(),
                ]);

                // บันทึก Anomaly ถ้าเป็นกรณี Fraud card
                if ($failureCode === 'fraud_suspected') {
                    SystemAnomaly::create([
                        'booking_id'        => $booking->b_booking_id,
                        'caused_by_user_id' => $booking->customer_id,
                        'type'              => 'PAYMENT_FRAUD_CARD_TESTING',
                        'message'           => 'Card flagged for suspected fraud during checkout',
                        'anomaly_code'      => 'ANOM_' . uniqid(),
                    ]);
                }
            }
        });
    }

    private function issueOrderAndTickets(Booking $booking, Payment $payment): void
    {
        // สร้าง Customer Order
        $order = $booking->customerOrder()->create([
            'payment_id'                  => $payment->p_payment_id,
            'order_code'                  => 'ORD_' . strtoupper(uniqid()),
            'concert_name_snapshot'       => $booking->concert->name,
            'artist_snapshot'             => $booking->concert->artist,
            'venue_snapshot'              => $booking->concert->venue,
            'event_datetime_snapshot'     => $booking->concert->event_datetime,
            'organization_name_snapshot'  => $booking->concert->organization->name,
            'customer_fiestname_snapshot' => $booking->customer->first_name,
            'customer_lastname_snapshot'  => $booking->customer->last_name,
            'customer_phone_snapshot'     => $booking->customer->phone,
        ]);

        // ออก Ticket สำหรับแต่ละที่นั่ง
        foreach ($booking->seats as $seat) {
            $order->tickets()->create([
                'zone_id'             => $seat->zone_id,
                'seat_id'             => $seat->seat_id,
                'qr_code'             => 'TCK_' . hash('sha256', $order->order_id . '_' . $seat->seat_id),
                'ticket_code'         => 'TKT_' . strtoupper(uniqid()),
                'zone_name_snapshot'  => $seat->zone->name,
                'seat_label_snapshot' => $seat->seat_number,
                'unit_price_snapshot' => $seat->price,
            ]);
        }
    }
}
```

---

### 3.4 Reconcile Scheduler (`ReconcilePaymentsCommand.php`)
ทำงานทุกนาที เพื่อตามเก็บสถานะในกรณีที่ Webhook สูญหาย (`webhook_lost`):

```php
<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\PaymentStateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ReconcilePaymentsCommand extends Command
{
    protected $signature   = 'payments:reconcile';
    protected $description = 'Poll PayMock for pending payments older than 1 minute';

    public function __construct(private readonly PaymentStateService $paymentState)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        Payment::where('p_status', 'PENDING')
            ->where('created_at', '<', now()->subMinute())
            ->whereNotNull('p_provider_transaction_id')
            ->each(function (Payment $payment) {
                try {
                    $response = Http::withToken(config('services.paymock.key'))
                        ->timeout(5)
                        ->get(config('services.paymock.url') . '/v1/payments/' . $payment->p_provider_transaction_id);

                    if ($response->successful()) {
                        $data = $response->json();
                        // ถ้าสถานะใน gateway เปลี่ยนไปแล้ว ให้อัปเดต
                        if ($data['status'] !== 'PENDING') {
                            $this->paymentState->apply(
                                providerId:  $data['id'],
                                status:      $data['status'],
                                amount:      (float) $data['amount'],
                                failureCode: $data['failure_code'] ?? null
                            );
                            $this->info("Reconciled {$payment->p_provider_transaction_id}: {$data['status']}");
                        }
                    }
                } catch (\Exception $e) {
                    $this->warn("Failed to reconcile {$payment->p_provider_transaction_id}: " . $e->getMessage());
                }
            });

        return self::SUCCESS;
    }
}
```
