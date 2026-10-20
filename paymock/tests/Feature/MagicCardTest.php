<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MagicCardTest extends TestCase
{
    use RefreshDatabase;

    private function createPendingPayment(): Payment
    {
        return Payment::create([
            'payment_id'      => (string) random_int(1000, 999999),
            'order_ref'       => 'BOOK_' . uniqid(),
            'amount'          => 1200,
            'method'          => 'card',
            'status'          => 'PENDING',
            'callback_url'    => 'http://concert/webhooks/payment',
            'return_url'      => 'http://concert.local/booking/42',
            'idempotency_key' => 'IDEMP_' . uniqid(),
        ]);
    }

    /** 4242 4242 4242 4242 → COMPLETED */
    public function test_success_card_completes_payment(): void
    {
        Queue::fake();
        $payment = $this->createPendingPayment();

        $response = $this->post(route('payments.process', $payment->public_id), [
            'card_number' => '4242 4242 4242 4242',
            'expiry'      => '12/28',
            'cvc'         => '123',
            'cardholder'  => 'JOHN DOE',
            'redirect_to_return' => '1',
        ]);

        $response->assertRedirect('http://concert.local/booking/42?payment_id=' . $payment->public_id);

        $this->assertEquals('COMPLETED', $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->paid_at);
        $this->assertDatabaseHas('webhook_deliveries', [
            'payment_id' => $payment->id,
            'event'      => 'payment.completed',
        ]);
    }

    /** 4000 0000 0000 0002 → FAILED (card_declined) */
    public function test_declined_card_fails_payment(): void
    {
        Queue::fake();
        $payment = $this->createPendingPayment();

        $this->post(route('payments.process', $payment->public_id), [
            'card_number' => '4000 0000 0000 0002',
            'expiry'      => '12/28',
            'cvc'         => '123',
            'cardholder'  => 'JOHN DOE',
        ]);

        $p = $payment->fresh();
        $this->assertEquals('FAILED', $p->status);
        $this->assertEquals('card_declined', $p->failure_code);
    }

    /** 4000 0000 0000 9995 → FAILED (insufficient_funds) */
    public function test_insufficient_funds_card(): void
    {
        Queue::fake();
        $payment = $this->createPendingPayment();

        $this->post(route('payments.process', $payment->public_id), [
            'card_number' => '4000 0000 0000 9995',
            'expiry'      => '12/28',
            'cvc'         => '123',
            'cardholder'  => 'JOHN DOE',
        ]);

        $this->assertEquals('insufficient_funds', $payment->fresh()->failure_code);
    }

    /** 4000 0000 0000 0069 → FAILED (expired_card) */
    public function test_expired_card(): void
    {
        Queue::fake();
        $payment = $this->createPendingPayment();

        $this->post(route('payments.process', $payment->public_id), [
            'card_number' => '4000 0000 0000 0069',
            'expiry'      => '12/28',
            'cvc'         => '123',
            'cardholder'  => 'JOHN DOE',
        ]);

        $this->assertEquals('expired_card', $payment->fresh()->failure_code);
    }

    /** 4100 0000 0000 0019 → FAILED (fraud_suspected) */
    public function test_fraud_card(): void
    {
        Queue::fake();
        $payment = $this->createPendingPayment();

        $this->post(route('payments.process', $payment->public_id), [
            'card_number' => '4100 0000 0000 0019',
            'expiry'      => '12/28',
            'cvc'         => '123',
            'cardholder'  => 'FRAUD BOT',
        ]);

        $this->assertEquals('fraud_suspected', $payment->fresh()->failure_code);
    }
}
