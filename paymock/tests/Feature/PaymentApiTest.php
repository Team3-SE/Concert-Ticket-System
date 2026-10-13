<?php

namespace Tests\Feature;

use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = config('paymock.api_key');
    }

    /** Test: สร้าง payment สำเร็จ คืน 201 พร้อม checkout_url */
    public function test_can_create_payment(): void
    {
        $response = $this->withHeaders([
            'Authorization'   => 'Bearer ' . $this->apiKey,
            'Idempotency-Key' => 'BOOKING_001',
        ])->postJson('/v1/payments', [
            'order_ref'    => 'BOOKING_001',
            'amount'       => 1500.00,
            'method'       => 'card',
            'callback_url' => 'http://concert/webhooks/payment',
            'return_url'   => 'http://localhost:8080/return',
            'expires_in'   => 600,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id', 'order_ref', 'amount', 'method', 'status',
                'checkout_url', 'expires_at', 'created_at',
            ])
            ->assertJson([
                'order_ref' => 'BOOKING_001',
                'amount'    => 1500.0,
                'status'    => 'PENDING',
            ]);

        $this->assertDatabaseHas('payments', [
            'order_ref'       => 'BOOKING_001',
            'idempotency_key' => 'BOOKING_001',
            'status'          => 'PENDING',
        ]);
    }

    /** Test: Idempotency-Key เดิม คืน payment เดิม ไม่สร้างใหม่ */
    public function test_idempotent_creation_returns_same_payment(): void
    {
        $payload = [
            'order_ref'    => 'BOOKING_DUP',
            'amount'       => 2000.00,
            'method'       => 'promptpay',
            'callback_url' => 'http://concert/webhooks/payment',
        ];

        $res1 = $this->withHeaders([
            'Authorization'   => 'Bearer ' . $this->apiKey,
            'Idempotency-Key' => 'KEY_SAME',
        ])->postJson('/v1/payments', $payload);

        $res2 = $this->withHeaders([
            'Authorization'   => 'Bearer ' . $this->apiKey,
            'Idempotency-Key' => 'KEY_SAME',
        ])->postJson('/v1/payments', $payload);

        $this->assertEquals(1, Payment::count());
        $this->assertEquals($res1->json('id'), $res2->json('id'));
    }

    /** Test: GET /v1/payments/{id} คืนสถานะถูกต้อง */
    public function test_can_query_payment_status(): void
    {
        $payment = Payment::create([
            'order_ref'       => 'BOOKING_STATUS',
            'amount'          => 500,
            'method'          => 'card',
            'status'          => 'PENDING',
            'callback_url'    => 'http://test/cb',
            'idempotency_key' => 'KEY_STAT',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->getJson('/v1/payments/' . $payment->public_id);

        $response->assertStatus(200)
            ->assertJson([
                'id'     => $payment->public_id,
                'status' => 'PENDING',
                'amount' => 500.0,
            ]);
    }

    /** Test: POST /v1/payments/{id}/cancel ยกเลิกได้ก่อนจ่าย */
    public function test_can_cancel_pending_payment(): void
    {
        $payment = Payment::create([
            'order_ref'       => 'BOOKING_CANCEL',
            'amount'          => 800,
            'method'          => 'card',
            'status'          => 'PENDING',
            'callback_url'    => 'http://test/cb',
            'idempotency_key' => 'KEY_CANCEL',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->postJson('/v1/payments/' . $payment->public_id . '/cancel');

        $response->assertStatus(200)
            ->assertJson(['ok' => true, 'status' => 'FAILED', 'failure_code' => 'cancelled']);

        $this->assertEquals('FAILED', $payment->fresh()->status);
    }

    /** Test: API Key ผิด คืน 401 */
    public function test_rejects_invalid_api_key(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer wrong_token',
        ])->postJson('/v1/payments', [
            'order_ref'    => 'B1',
            'amount'       => 100,
            'method'       => 'card',
            'callback_url' => 'http://test/cb',
        ]);

        $response->assertStatus(401);
    }
}
