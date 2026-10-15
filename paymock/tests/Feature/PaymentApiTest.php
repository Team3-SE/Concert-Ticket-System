<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Setting;
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
            'payment_id'   => '101',
            'amount'       => 1500.00,
            'method'       => 'card',
            'callback_url' => 'http://concert/webhooks/payment',
            'return_url'   => 'http://localhost:8080/return',
            'expires_in'   => 600,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id', 'provider_transaction_id', 'payment_id', 'amount', 'method', 'status',
                'expires_at', 'created_at',
            ])
            ->assertJson([
                'payment_id' => '101',
                'amount'     => 1500.0,
                'status'     => 'PENDING',
            ]);

        $this->assertEquals($response->json('id'), $response->json('provider_transaction_id'));
        $this->assertDatabaseHas('payments', [
            'payment_id'      => '101',
            'idempotency_key' => 'BOOKING_001',
            'status'          => 'PENDING',
        ]);
    }

    /** Test: PromptPay PENDING คืน payment_id ของร้านค้า ไม่เป็น null */
    public function test_promptpay_create_returns_merchant_payment_id(): void
    {
        $response = $this->withHeaders([
            'Authorization'   => 'Bearer ' . $this->apiKey,
            'Idempotency-Key' => 'PP_PENDING_001',
        ])->postJson('/v1/payments', [
            'payment_id'   => '555',
            'amount'       => 2200.00,
            'method'       => 'promptpay',
            'callback_url' => 'http://concert/webhooks/payment',
            'expires_in'   => 600,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'payment_id' => '555',
                'method'     => 'promptpay',
                'status'     => 'PENDING',
            ]);
        $this->assertNotNull($response->json('payment_id'));
        $this->assertNotEmpty($response->json('qrcode_url'));
    }

    /** Test: body เก่าส่ง order_ref อย่างเดียว ยังต้องเติม payment_id ใน response */
    public function test_create_with_order_ref_alias_fills_payment_id(): void
    {
        $response = $this->withHeaders([
            'Authorization'   => 'Bearer ' . $this->apiKey,
            'Idempotency-Key' => 'LEGACY_ORDER_REF',
        ])->postJson('/v1/payments', [
            'order_ref'    => 'TK-261016-ABC12',
            'amount'       => 1800.00,
            'method'       => 'promptpay',
            'callback_url' => 'http://concert/webhooks/payment',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'payment_id' => 'TK-261016-ABC12',
                'status'     => 'PENDING',
            ]);
    }

    /** Test: Idempotency-Key เดิม คืน payment เดิม ไม่สร้างใหม่ */
    public function test_idempotent_creation_returns_same_payment(): void
    {
        $payload = [
            'payment_id'   => '202',
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
            'payment_id'      => '303',
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
                'id'         => $payment->public_id,
                'payment_id' => '303',
                'status'     => 'PENDING',
                'amount'     => 500.0,
            ]);
    }

    /** รายการเก่าที่ยังไม่มีคอลัมน์ payment_id ต้อง echo order_ref แทน ไม่คืน null */
    public function test_legacy_row_without_payment_id_uses_order_ref(): void
    {
        $payment = Payment::create([
            'order_ref'       => 'LEGACY_BOOKING',
            'amount'          => 900,
            'method'          => 'promptpay',
            'status'          => 'PENDING',
            'callback_url'    => 'http://test/cb',
            'idempotency_key' => 'KEY_LEGACY_NULL',
        ]);

        $this->assertNull($payment->getAttributes()['payment_id'] ?? null);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->getJson('/v1/payments/' . $payment->public_id);

        $response->assertStatus(200)
            ->assertJson([
                'payment_id' => 'LEGACY_BOOKING',
                'status'     => 'PENDING',
            ]);
    }

    /** Test: POST /v1/payments/{id}/cancel ยกเลิกได้ก่อนจ่าย */
    public function test_can_cancel_pending_payment(): void
    {
        $payment = Payment::create([
            'payment_id'      => '404',
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

    /** GET /v1/payments แสดง chaos_mode และ webhook_mode จาก Admin */
    public function test_index_exposes_admin_mock_modes(): void
    {
        Setting::set('chaos_mode', 'normal');
        Setting::set('webhook_mode', 'webhook_lost');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->getJson('/v1/payments');

        $response->assertStatus(200)
            ->assertJson([
                'status'       => 'online',
                'chaos_mode'   => 'normal',
                'webhook_mode' => 'webhook_lost',
            ]);
    }

    /** Test: API Key ผิด คืน 401 */
    public function test_rejects_invalid_api_key(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer wrong_token',
        ])->postJson('/v1/payments', [
            'payment_id'   => '1',
            'amount'       => 100,
            'method'       => 'card',
            'callback_url' => 'http://test/cb',
        ]);

        $response->assertStatus(401);
    }
}
