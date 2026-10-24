<?php

namespace Tests\Feature;

use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PromptPayTest extends TestCase
{
    use RefreshDatabase;

    private function createPendingPromptPay(array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'amount'          => 2500.00,
            'method'          => 'promptpay',
            'status'          => 'PENDING',
            'callback_url'    => 'http://concert/webhooks/payment',
            'idempotency_key' => 'PP_IDEMP_' . uniqid(),
            'expires_at'      => now()->addMinutes(10),
        ], $overrides));
    }

    /** PromptPay: กดจำลองจ่ายแล้ว (COMPLETED) */
    public function test_promptpay_pay_action_completes_payment(): void
    {
        Queue::fake();
        $payment = $this->createPendingPromptPay();

        $response = $this->post(route('checkout.promptpay', $payment->public_id), [
            'action' => 'pay',
        ]);

        $this->assertEquals('COMPLETED', $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->paid_at);
        $this->assertDatabaseHas('webhook_deliveries', [
            'payment_id' => $payment->id,
            'event'      => 'payment.completed',
        ]);
    }

    /** PromptPay: กดจำลองไม่จ่าย (FAILED) */
    public function test_promptpay_fail_action_fails_payment(): void
    {
        Queue::fake();
        $payment = $this->createPendingPromptPay();

        $this->post(route('checkout.promptpay', $payment->public_id), [
            'action' => 'fail',
        ]);

        $this->assertEquals('FAILED', $payment->fresh()->status);
        $this->assertEquals('payment_declined', $payment->fresh()->failure_code);
    }

    /** PromptPay: ไม่จ่ายจนหมดเวลา -> Expire Payments command เปลี่ยนเป็น EXPIRED */
    public function test_unpaid_promptpay_expires_via_command(): void
    {
        Queue::fake();
        $payment = $this->createPendingPromptPay([
            'expires_at' => now()->subMinute(), // หมดเวลาแล้ว
        ]);

        Artisan::call('payments:expire');

        $this->assertEquals('EXPIRED', $payment->fresh()->status);
        $this->assertDatabaseHas('webhook_deliveries', [
            'payment_id' => $payment->id,
            'event'      => 'payment.expired',
        ]);
    }

    /** PromptPay: Simulation API settles a pending payment */
    public function test_auto_pay_simulate_endpoint(): void
    {
        Queue::fake();
        $payment = $this->createPendingPromptPay();

        $response = $this->postJson(route('checkout.simulate', $payment->public_id), [
            'status' => 'COMPLETED',
        ]);

        $response->assertStatus(200)->assertJson(['ok' => true, 'status' => 'COMPLETED']);
        $this->assertEquals('COMPLETED', $payment->fresh()->status);
    }
}
