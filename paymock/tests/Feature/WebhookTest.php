<?php

namespace Tests\Feature;

use App\Jobs\SendWebhookJob;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\WebhookDelivery;
use App\Services\WebhookEmitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private function createPayment(string $status = 'COMPLETED'): Payment
    {
        return Payment::create([
            'payment_id'      => (string) random_int(1000, 999999),
            'order_ref'       => 'BOOK_' . uniqid(),
            'amount'          => 1500,
            'method'          => 'promptpay',
            'status'          => $status,
            'callback_url'    => 'http://concert/webhooks/payment',
            'idempotency_key' => 'ID_' . uniqid(),
        ]);
    }

    /** Webhook ปกติ: dispatch 1 job */
    public function test_normal_webhook_dispatches_one_job(): void
    {
        Queue::fake();
        $payment = $this->createPayment();
        app(WebhookEmitter::class)->emit($payment, 'payment.completed');

        Queue::assertPushed(SendWebhookJob::class, 1);
    }

    /** Webhook lost: ไม่ dispatch job เลย */
    public function test_webhook_lost_scenario_dispatches_nothing(): void
    {
        Queue::fake();
        Setting::set('webhook_mode', 'webhook_lost');
        $payment = $this->createPayment();
        app(WebhookEmitter::class)->emit($payment, 'payment.completed');

        Queue::assertNothingPushed();
    }

    /** Webhook mode จาก Admin มีผลกับทุกรายการ */
    public function test_global_webhook_mode_lost_dispatches_nothing(): void
    {
        Queue::fake();
        Setting::set('webhook_mode', 'webhook_lost');
        $payment = $this->createPayment();
        app(WebhookEmitter::class)->emit($payment, 'payment.completed');

        Queue::assertNothingPushed();
    }

    /** Webhook dup:3 จาก Admin: dispatch 3 jobs */
    public function test_webhook_dup_dispatches_multiple_jobs(): void
    {
        Queue::fake();
        Setting::set('webhook_mode', 'webhook_dup:3');
        $payment = $this->createPayment();
        app(WebhookEmitter::class)->emit($payment, 'payment.completed');

        Queue::assertPushed(SendWebhookJob::class, 3);
    }

    /** Webhook ส่งพร้อม signature ที่ถูกต้อง */
    public function test_webhook_job_sends_valid_hmac_signature(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        $payment  = $this->createPayment();
        $delivery = WebhookDelivery::create([
            'payment_id' => $payment->id,
            'event'      => 'payment.completed',
            'payload'    => ['id' => $payment->public_id, 'status' => 'COMPLETED'],
        ]);

        $job = new SendWebhookJob($delivery->id);
        $job->handle();

        Http::assertSent(function (\Illuminate\Http\Client\Request $req) {
            $sigHeader = $req->header('X-Signature')[0] ?? '';
            // ตรวจรูปแบบ t=xxx,v1=yyy
            $hasFormat = (bool) preg_match('/t=\d+,v1=[a-f0-9]{64}/', $sigHeader);
            $hasEventId = ! empty($req->header('X-Event-Id'));
            return $hasFormat && $hasEventId;
        });
    }

    /** Webhook bad_sig: ส่ง signature ผิด (000...000) */
    public function test_webhook_bad_sig_sends_zeroed_signature(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        $payment  = $this->createPayment();
        $delivery = WebhookDelivery::create([
            'payment_id' => $payment->id,
            'event'      => 'payment.completed',
            'payload'    => ['id' => $payment->public_id, 'status' => 'COMPLETED'],
        ]);

        $job = new SendWebhookJob($delivery->id, 'webhook_bad_sig');
        $job->handle();

        Http::assertSent(function (\Illuminate\Http\Client\Request $req) {
            $sigHeader = $req->header('X-Signature')[0] ?? '';
            return str_contains($sigHeader, 'v1=' . str_repeat('0', 64));
        });
    }
}
