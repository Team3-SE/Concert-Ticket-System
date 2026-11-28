<?php

/**
 * PaymockService
 * ติดต่อ PayMock (mock payment gateway ในโฟลเดอร์ paymentsgateway) สร้างรายการชำระเงิน ตรวจลายเซ็น webhook
 * และนำผลการชำระเงินมาปรับสถานะ payment/booking (ใช้ร่วมกันทั้ง webhook, scheduler และการ poll จากหน้าชำระเงิน)
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 20/11/2569
 * Updated : 20/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Services;

use App\Http\Controllers\User\PaymentController;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\SystemAnomaly;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymockService
{
    /* ช่องทางชำระเงินของระบบหลัก → ค่าที่ PayMock รับ */
    private const METHOD_MAP = ['PROMPTPAY' => 'promptpay', 'CREDIT_CARD' => 'card'];

    /**
     * enabled()
     * ตรวจว่าตั้งค่า PayMock ไว้ครบหรือไม่ (ไม่ครบ = ใช้การจำลองแบบเดิม)
     * param : -
     * return : bool
     */
    public function enabled(): bool
    {
        return filled(config('services.paymock.url')) && filled(config('services.paymock.api_key'));
    }

    /**
     * createPayment(payment, booking, attempt)
     * สร้างรายการชำระเงินที่ PayMock แล้วเก็บ id / qrcode_url / checkout_url ไว้ใน payment
     * param : payment (PENDING), booking, attempt (ครั้งที่สร้างที่ gateway ของ payment นี้)
     * return : array ข้อมูล gateway { id, qrcode_url, checkout_url, attempt }
     * throws : \RuntimeException เมื่อติดต่อ gateway ไม่ได้หรือถูกปฏิเสธ
     */
    public function createPayment(Payment $payment, Booking $booking, int $attempt): array
    {
        $expiresIn = $booking->expiry_time ? (int) now()->diffInSeconds($booking->expiry_time, false) : 600;
        $orderCode = $payment->payment_metadata['orderCode'] ?? null;

        $body = [
            'payment_id'   => $payment->p_id . '-' . $attempt,
            'booking_ref'  => $orderCode,
            'amount'       => (float) $payment->amount,
            'method'       => self::METHOD_MAP[$payment->method] ?? 'promptpay',
            'callback_url' => config('services.paymock.callback_url'),
            // Redirect กลับเป็นเพียงจุดให้ browser กลับเข้าเว็บเราเท่านั้น
            // ปลายทางจะอ่านสถานะจาก PayMock ซ้ำก่อนตัดสินผล จึงไม่เชื่อ
            // query string หรือผลของ redirect จาก browser.
            'return_url'   => route('payments.gateway-return', ['bookingId' => $booking->b_id]),
            'expires_in'   => max(60, $expiresIn),
            'metadata'     => ['p_id' => $payment->p_id, 'b_id' => $booking->b_id],
        ];

        $response = $this->client()
            ->withHeaders(['Idempotency-Key' => $body['payment_id']])
            ->post('/v1/payments', $body);

        $responseData = $response->json();
        $expectedMethod = self::METHOD_MAP[$payment->method] ?? null;
        $isValidResponse = $response->successful()
            && is_array($responseData)
            && filled($responseData['id'] ?? null)
            && ($responseData['method'] ?? null) === $expectedMethod
            && strtoupper((string) ($responseData['currency'] ?? 'THB')) === 'THB'
            && abs((float) ($responseData['amount'] ?? -1) - (float) $payment->amount) < 0.01;

        if (! $isValidResponse) {
            Log::warning('PayMock create failed', ['p_id' => $payment->p_id, 'status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('PayMock create payment failed: HTTP ' . $response->status());
        }

        return [
            'id'           => $response->json('id'),
            'qrcode_url'   => $response->json('qrcode_url'),
            'checkout_url' => $response->json('checkout_url'),
            'bank_simulator_url' => $response->json('bank_simulator_url'),
            'attempt'      => $attempt,
        ];
    }

    /**
     * publicUrl(url)
     * แปลง URL ที่ PayMock ส่งมา (สร้างจาก host ที่ server เรียก เช่น host.docker.internal ซึ่งเบราว์เซอร์เปิดไม่ได้)
     * ให้เป็น URL ที่เบราว์เซอร์ผู้ใช้เปิดได้ โดยคงเฉพาะ path/query แล้วใช้ PAYMOCK_PUBLIC_URL เป็นฐาน
     * param : url (null ได้)
     * return : string|null
     */
    public function publicUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $parts = parse_url($url);
        $path = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');

        return rtrim((string) config('services.paymock.public_url'), '/') . $path;
    }

    /**
     * fetchQrImage(providerId)
     * ดึงภาพ QR (SVG) ที่ PayMock สร้างให้รายการนี้ผ่าน server-to-server แล้วแปลงเป็น data URI ให้หน้าเว็บแสดงได้โดยไม่ต้องเข้า PayMock เอง
     * PayMock สร้างภาพใหม่ทุกครั้งที่ถูกเรียก แต่เนื้อหา QR ผูกกับรายการ (payment) เดียวกัน จึงเหมือนเดิมเสมอ
     * param : providerId
     * return : string|null data URI หรือ null ถ้าดึงไม่ได้/ไม่ใช่ SVG
     */
    public function fetchQrImage(string $providerId): ?string
    {
        try {
            $response = $this->client()->get('/v1/payments/' . urlencode($providerId) . '/qr.svg');
            $body = ltrim($response->body());

            if (! $response->successful() || ! str_contains(substr($body, 0, 300), '<svg')) {
                return null;
            }

            return 'data:image/svg+xml;base64,' . base64_encode($body);
        } catch (\Throwable $e) {
            Log::info('PayMock QR fetch failed', ['id' => $providerId, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * cancelPayment(providerId)
     * ยกเลิกรายการที่ PayMock (ใช้เมื่อผู้ใช้เปลี่ยนช่องทาง/ยอด จึงต้องสร้างรายการใหม่) ผิดพลาดแล้วข้ามได้
     * param : providerId
     * return : void
     */
    public function cancelPayment(string $providerId): void
    {
        try {
            $this->client()->post('/v1/payments/' . urlencode($providerId) . '/cancel');
        } catch (\Throwable $e) {
            Log::info('PayMock cancel skipped', ['id' => $providerId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * fetchPayment(providerId)
     * ดูสถานะรายการที่ PayMock (ใช้ตอน reconcile/poll)
     * param : providerId
     * return : array|null ข้อมูลรายการ หรือ null ถ้าติดต่อไม่ได้
     */
    public function fetchPayment(string $providerId): ?array
    {
        try {
            $response = $this->client()->get('/v1/payments/' . urlencode($providerId));

            return $response->successful() ? $response->json() : null;
        } catch (\Throwable $e) {
            Log::warning('PayMock fetch failed', ['id' => $providerId, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * verifySignature(header, rawBody)
     * ตรวจลายเซ็น webhook: X-Signature = t={timestamp},v1={HMAC-SHA256("t.rawBody")} และ timestamp ต้องไม่เก่าเกินที่กำหนด
     * param : header (ค่า X-Signature), rawBody (body ดิบ)
     * return : bool
     */
    public function verifySignature(?string $header, string $rawBody): bool
    {
        $secret = (string) config('services.paymock.webhook_secret');
        if ($header === null || $secret === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $piece) {
            [$k, $v] = array_pad(explode('=', trim($piece), 2), 2, '');
            $parts[$k] = $v;
        }

        $timestamp = $parts['t'] ?? '';
        $signature = $parts['v1'] ?? '';
        if (! ctype_digit($timestamp) || $signature === '') {
            return false;
        }
        if (abs(time() - (int) $timestamp) > (int) config('services.paymock.tolerance', 300)) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret), $signature);
    }

    /**
     * findPayment(data)
     * หา payment ของระบบหลักจากข้อมูลที่ PayMock ส่งมา (ใช้ id ของ gateway ก่อน ไม่เจอค่อยใช้ payment_id รูปแบบ "{p_id}-{attempt}")
     * รายการที่ถูกแทนที่ด้วยครั้งใหม่แล้ว provider_transaction_id จะเปลี่ยน จึงไม่ตรงและถูกข้าม
     * param : data
     * return : Payment|null
     */
    public function findPayment(array $data): ?Payment
    {
        if (! empty($data['id'])) {
            return Payment::where('provider_transaction_id', $data['id'])->first();
        }

        return null;
    }

    /**
     * applyResult(payment, data, source)
     * นำสถานะจาก gateway มาปรับ payment/booking (idempotent: ทำเฉพาะ payment ที่ยัง PENDING)
     * COMPLETED → ยืนยันผ่าน completePayment() (ยอดไม่ตรง/การจองหมดเวลาแล้ว = บันทึก SystemAnomaly ไม่ออกบัตร)
     * FAILED → ตั้ง payment เป็น FAILED (การจองยังอยู่จนหมดเวลา ผู้ใช้ชำระใหม่ได้)
     * param : payment, data (ข้อมูลจาก gateway), source (webhook|reconcile|poll)
     * return : string completed|failed|anomaly|pending|ignored
     */
    public function applyResult(Payment $payment, array $data, string $source): string
    {
        $status = strtoupper((string) ($data['status'] ?? ''));
        if (! in_array($status, ['COMPLETED', 'FAILED'], true)) {
            return 'pending';
        }

        return DB::transaction(function () use ($payment, $data, $status, $source) {
            $locked = Payment::with('booking')->whereKey($payment->p_id)->lockForUpdate()->first();
            if ($locked === null || $locked->status !== Payment::STATUS_PENDING) {
                return 'ignored';   // ประมวลผลไปแล้ว (webhook ซ้ำ/ส่งหลัง reconcile)
            }

            $booking = $locked->booking;
            $metadata = array_merge($locked->payment_metadata ?? [], [
                'gateway_result' => ['status' => $status, 'source' => $source, 'at' => now()->toIso8601String(), 'failure_code' => $data['failure_code'] ?? null],
            ]);

            if ($status === 'FAILED') {
                $locked->update(['status' => Payment::STATUS_FAILED, 'payment_metadata' => $metadata]);

                return 'failed';
            }

            // COMPLETED
            $paidAmount = (float) ($data['amount'] ?? 0);
            if (abs($paidAmount - (float) $locked->amount) > 0.009) {
                $this->recordAnomaly($booking, 'PAYMENT_AMOUNT_MISMATCH', $locked->p_id,
                    "ยอดที่ gateway รับ ({$paidAmount}) ไม่ตรงกับยอดที่ระบบเรียกเก็บ ({$locked->amount}) payment #{$locked->p_id}");
                $locked->update(['status' => Payment::STATUS_FAILED, 'payment_metadata' => $metadata + ['anomaly' => 'amount_mismatch']]);

                return 'anomaly';
            }

            if ($booking === null || $booking->status !== Booking::STATUS_PENDING_PAYMENT) {
                $this->recordAnomaly($booking, 'LATE_PAYMENT', $locked->p_id,
                    "ได้รับเงินจาก gateway หลังการจองหมดเวลา/ถูกยกเลิก (ที่นั่งถูกปล่อยแล้ว) payment #{$locked->p_id} ต้องคืนเงินหรือจัดการด้วยมือ");
                $locked->update(['status' => Payment::STATUS_FAILED, 'payment_metadata' => $metadata + ['anomaly' => 'late_payment']]);

                return 'anomaly';
            }

            $locked->update(['payment_metadata' => $metadata]);
            app(PaymentController::class)->completePayment($locked->p_id);

            return 'completed';
        });
    }

    /**
     * syncPayment(payment, source)
     * ถาม gateway ถึงสถานะล่าสุดของ payment แล้วปรับสถานะ (ใช้ตอน reconcile/poll เมื่อ webhook ไม่มา)
     * param : payment, source
     * return : string ผลเหมือน applyResult() หรือ unreachable
     */
    public function syncPayment(Payment $payment, string $source): string
    {
        if (! $payment->provider_transaction_id) {
            return 'ignored';
        }

        $data = $this->fetchPayment($payment->provider_transaction_id);

        return $data === null ? 'unreachable' : $this->applyResult($payment, $data, $source);
    }

    /**
     * recordAnomaly(booking, type, paymentId, message)
     * บันทึกความผิดปกติของการชำระเงินให้ผู้ดูแลระบบตรวจ (anomaly_code ซ้ำไม่ได้ จึงบันทึกครั้งเดียวต่อ payment)
     * param : booking (null ได้ = ข้าม), type, paymentId, message
     * return : void
     */
    private function recordAnomaly(?Booking $booking, string $type, int $paymentId, string $message): void
    {
        Log::warning('PayMock anomaly: ' . $type, ['p_id' => $paymentId, 'message' => $message]);

        if ($booking === null) {
            return;
        }

        SystemAnomaly::firstOrCreate(
            ['anomaly_code' => "PAYMOCK-{$type}-{$paymentId}"],
            [
                'sa_booking_id'        => $booking->b_id,
                'sa_caused_by_user_id' => $booking->b_user_id,
                'type'                 => $type,
                'message'              => $message,
                'status'               => 'OPEN',
                'detected_at'          => now(),
            ]
        );
    }

    /**
     * client()
     * HTTP client ไป PayMock (Bearer API key, timeout สั้น ไม่ให้หน้าเว็บค้าง)
     * param : -
     * return : PendingRequest
     */
    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.paymock.url'), '/'))
            ->withToken((string) config('services.paymock.api_key'))
            ->acceptJson()
            ->timeout(8)
            ->connectTimeout(4);
    }
}
