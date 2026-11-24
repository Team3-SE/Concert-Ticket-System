<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = config('paymock.api_key');
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
    }

    /** Test: สร้าง card payment สำเร็จ คืน checkout_url */
    public function test_can_create_payment(): void
    {
        $response = $this->withHeaders([
            'Authorization'   => 'Bearer ' . $this->apiKey,
            'Idempotency-Key' => 'BOOKING_001',
        ])->postJson('/payments', [
            'amount'       => 1500.00,
            'method'       => 'card',
            'callback_url' => 'http://concert/webhooks/payment',
            'return_url'   => 'http://localhost:8080/return',
            'expires_in'   => 600,
            'metadata'     => ['booking_id' => 42],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id', 'amount', 'method', 'status', 'metadata' => ['booking_id'],
                'checkout_url', 'return_url', 'expires_at', 'created_at',
            ])
            ->assertJson([
                'amount'     => 1500,
                'status'     => 'PENDING',
                'metadata'   => [
                    'booking_id'  => 42,
                ],
            ])
            ->assertJsonPath('metadata', ['booking_id' => 42]);

        $this->assertStringStartsWith('txn_', $response->json('id'));
        $this->assertNull($response->json('provider_transaction_id'));
        $response->assertJsonMissingPath('qrcode_url')
            ->assertJsonMissingPath('bank_simulator_url');
        $response->assertJsonMissingPath('payment_id')
            ->assertJsonMissingPath('booking_ref');
        $this->assertDatabaseHas('payments', [
            'idempotency_key' => 'BOOKING_001',
            'status'          => 'PENDING',
            'return_url'      => 'http://localhost:8080/return',
        ]);
        $response->assertJsonMissingPath('order_ref');
    }

    public function test_merchant_reference_columns_are_not_stored_or_returned(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->postJson('/payments', [
            'payment_id'   => 'LEGACY-101',
            'order_ref'    => 'TK-LEGACY-101',
            'booking_ref'  => 'TK-LEGACY-101',
            'amount'       => 1500,
            'method'       => 'card',
            'callback_url' => 'http://concert/webhooks/payment',
        ]);

        $response->assertCreated()
            ->assertJsonMissingPath('metadata')
            ->assertJsonMissingPath('payment_id')
            ->assertJsonMissingPath('booking_ref')
            ->assertJsonMissingPath('order_ref');
        $this->assertSame(1, Payment::count());
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('payments', 'payment_id'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('payments', 'booking_ref'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('payments', 'order_ref'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('webhook_deliveries', 'payment_id'));
    }

    public function test_promptpay_create_does_not_add_metadata_when_omitted(): void
    {
        $response = $this->withHeaders([
            'Authorization'   => 'Bearer ' . $this->apiKey,
            'Idempotency-Key' => 'PP_PENDING_001',
        ])->postJson('/payments', [
            'amount'       => 2200.00,
            'method'       => 'promptpay',
            'callback_url' => 'http://concert/webhooks/payment',
            'expires_in'   => 600,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'method'     => 'promptpay',
                'status'     => 'PENDING',
            ])
            ->assertJsonMissingPath('metadata');
        $this->assertDatabaseHas('payments', ['idempotency_key' => 'PP_PENDING_001']);
        $this->assertStringStartsWith('txn_', $response->json('id'));
        $this->assertNotEmpty($response->json('qrcode_url'));
        $this->assertNotEmpty($response->json('bank_simulator_url'));
        $response->assertJsonMissingPath('checkout_url');
    }

    public function test_create_does_not_require_merchant_references(): void
    {
        $response = $this->withHeaders([
            'Authorization'   => 'Bearer ' . $this->apiKey,
        ])->postJson('/payments', [
            'amount'       => 1800.00,
            'method'       => 'promptpay',
            'callback_url' => 'http://concert/webhooks/payment',
        ]);

        $response->assertCreated()
            ->assertJsonMissingPath('metadata');
        $this->assertStringStartsWith('txn_', $response->json('id'));
        $this->assertDatabaseHas('payments', [
            'metadata'   => null,
        ]);

        $statusResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->getJson('/payments/' . $response->json('id'));

        $statusResponse->assertOk()
            ->assertJsonMissingPath('metadata');
    }

    /** Test: Idempotency-Key เดิม คืน payment เดิม ไม่สร้างใหม่ */
    public function test_idempotent_creation_returns_same_payment(): void
    {
        $payload = [
            'amount'       => 2000.00,
            'method'       => 'promptpay',
            'callback_url' => 'http://concert/webhooks/payment',
        ];

        $res1 = $this->withHeaders([
            'Authorization'   => 'Bearer ' . $this->apiKey,
            'Idempotency-Key' => 'KEY_SAME',
        ])->postJson('/payments', $payload);

        $res2 = $this->withHeaders([
            'Authorization'   => 'Bearer ' . $this->apiKey,
            'Idempotency-Key' => 'KEY_SAME',
        ])->postJson('/payments', $payload);

        $this->assertEquals(1, Payment::count());
        $this->assertEquals($res1->json('id'), $res2->json('id'));
    }

    public function test_idempotency_header_deduplicates_requests(): void
    {
        $first = $this->withHeaders([
            'Authorization'   => 'Bearer ' . $this->apiKey,
            'Idempotency-Key' => 'HEADER_KEY_202',
        ])->postJson('/payments', [
            'amount'       => 2000,
            'method'       => 'promptpay',
            'callback_url' => 'http://concert/webhooks/payment',
        ]);

        $second = $this->withHeaders([
            'Authorization'   => 'Bearer ' . $this->apiKey,
            'Idempotency-Key' => 'HEADER_KEY_202',
        ])->postJson('/payments', [
            'amount'       => 2000,
            'method'       => 'promptpay',
            'callback_url' => 'http://concert/webhooks/payment',
        ]);

        $first->assertCreated();
        $second->assertCreated()->assertJsonPath('id', $first->json('id'));
        $this->assertSame(1, Payment::count());
        $this->assertDatabaseHas('payments', ['idempotency_key' => 'HEADER_KEY_202']);
    }

    public function test_requests_without_idempotency_header_create_new_payments(): void
    {
        $payload = [
            'amount'       => 2000.00,
            'method'       => 'promptpay',
            'callback_url' => 'http://concert/webhooks/payment',
        ];

        $first = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->postJson('/payments', $payload);

        $second = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->postJson('/payments', $payload);

        $first->assertCreated()->assertJsonMissingPath('metadata');
        $second->assertCreated()->assertJsonMissingPath('metadata');
        $this->assertNotSame($first->json('id'), $second->json('id'));
        $this->assertSame(2, Payment::count());
    }

    public function test_metadata_does_not_affect_idempotency(): void
    {
        $payload = [
            'amount'       => 2000,
            'method'       => 'promptpay',
            'callback_url' => 'http://concert/webhooks/payment',
            'metadata'     => [
                'source'      => 'concert',
                'custom_context' => ['order' => ['reference' => 'ABC'], 'enabled' => true],
            ],
        ];

        $first = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->postJson('/payments', $payload);
        $second = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->postJson('/payments', $payload);

        $first->assertCreated()
            ->assertJsonPath('metadata.source', 'concert')
            ->assertJsonPath('metadata.custom_context.order.reference', 'ABC');
        $second->assertCreated()->assertJsonPath('metadata.source', 'concert');
        $this->assertNotSame($first->json('id'), $second->json('id'));
        $this->assertSame(2, Payment::count());
    }

    public function test_metadata_accepts_custom_fields(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->postJson('/payments', [
            'amount'       => 2000,
            'method'       => 'promptpay',
            'callback_url' => 'http://concert/webhooks/payment',
            'metadata'     => ['custom_context' => ['source' => 'mobile_app']],
        ]);

        $response->assertCreated()
            ->assertJsonPath('metadata.custom_context.source', 'mobile_app');
    }

    public function test_metadata_accepts_non_object_json_values_without_validation(): void
    {
        $metadata = 'unstructured merchant metadata';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->postJson('/payments', [
            'amount'       => 2000,
            'method'       => 'promptpay',
            'callback_url' => 'http://concert/webhooks/payment',
            'metadata'     => $metadata,
        ]);

        $response->assertCreated()->assertJsonPath('metadata', $metadata);
        $this->assertSame($metadata, Payment::firstOrFail()->metadata);
    }

    public function test_metadata_is_not_validated_or_rewritten(): void
    {
        $metadata = ['custom_value' => ['nested' => str_repeat('X', 300)], 'source' => ['app' => 'mobile']];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->postJson('/payments', [
            'amount'       => 2000,
            'method'       => 'promptpay',
            'callback_url' => 'http://concert/webhooks/payment',
            'metadata'     => $metadata,
        ]);

        $response->assertCreated()
            ->assertJsonPath('metadata.custom_value.nested', $metadata['custom_value']['nested'])
            ->assertJsonPath('metadata.source.app', 'mobile');
    }

    public function test_header_only_idempotency_works(): void
    {
        $payload = [
            'amount'       => 2000,
            'method'       => 'promptpay',
            'callback_url' => 'http://concert/webhooks/payment',
        ];

        $first = $this->withHeaders([
            'Authorization'   => 'Bearer ' . $this->apiKey,
            'Idempotency-Key' => 'HEADER_ONLY_202',
        ])->postJson('/payments', $payload);
        $second = $this->withHeaders([
            'Authorization'   => 'Bearer ' . $this->apiKey,
            'Idempotency-Key' => 'HEADER_ONLY_202',
        ])->postJson('/payments', $payload);

        $first->assertCreated()
            ->assertJsonMissingPath('metadata')
            ->assertJsonMissingPath('idempotency_key');
        $second->assertCreated()->assertJsonPath('id', $first->json('id'));
        $this->assertSame(1, Payment::count());
    }

    /** Test: GET /payments/{id} คืนสถานะถูกต้อง */
    public function test_can_query_payment_status(): void
    {
        $payment = Payment::create([
            'amount'          => 500,
            'method'          => 'card',
            'status'          => 'PENDING',
            'callback_url'    => 'http://test/cb',
            'idempotency_key' => 'KEY_STAT',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->getJson('/payments/' . $payment->public_id);

        $response->assertStatus(200)
            ->assertJson([
                'id'         => $payment->public_id,
                'status'     => 'PENDING',
                'amount'     => 500.0,
            ])
            ->assertJsonMissingPath('metadata')
            ->assertJsonMissingPath('payment_id')
            ->assertJsonMissingPath('booking_ref');
    }

    public function test_payment_without_metadata_does_not_synthesize_metadata(): void
    {
        $payment = Payment::create([
            'amount'          => 900,
            'method'          => 'promptpay',
            'status'          => 'PENDING',
            'callback_url'    => 'http://test/cb',
            'idempotency_key' => 'KEY_LEGACY_NULL',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->getJson('/payments/' . $payment->public_id);

        $response->assertStatus(200)
            ->assertJsonMissingPath('metadata')
            ->assertJsonPath('status', 'PENDING');
    }

    public function test_card_expires_in_is_applied(): void
    {
        $createdAt = now();
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->postJson('/payments', [
            'amount'       => 1200,
            'method'       => 'card',
            'callback_url' => 'http://concert/webhooks/payment',
            'expires_in'   => 900,
        ]);

        $response->assertCreated()->assertJsonStructure(['expires_at']);
        $expiresAt = \Illuminate\Support\Carbon::parse($response->json('expires_at'));
        $this->assertGreaterThanOrEqual($createdAt->copy()->addSeconds(900)->timestamp, $expiresAt->timestamp);
        $this->assertLessThanOrEqual(now()->addSeconds(900)->timestamp, $expiresAt->timestamp);
    }

    public function test_qr_endpoint_is_public_and_returns_svg_headers(): void
    {
        $payment = Payment::create([
            'amount'          => 800,
            'method'          => 'promptpay',
            'status'          => 'PENDING',
            'callback_url'    => 'http://test/cb',
            'idempotency_key' => 'KEY_QR',
        ]);

        $response = $this->get('/payments/' . $payment->public_id . '/qr.svg');

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
    }

    /** Test: POST /payments/{id}/cancel ยกเลิกได้ก่อนจ่าย */
    public function test_can_cancel_pending_payment(): void
    {
        $payment = Payment::create([
            'amount'          => 800,
            'method'          => 'card',
            'status'          => 'PENDING',
            'callback_url'    => 'http://test/cb',
            'idempotency_key' => 'KEY_CANCEL',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->postJson('/payments/' . $payment->public_id . '/cancel');

        $response->assertStatus(200)
            ->assertJson(['ok' => true, 'status' => 'FAILED', 'failure_code' => 'cancelled']);

        $this->assertEquals('FAILED', $payment->fresh()->status);
    }

    /** GET /payments แสดง chaos_mode และ webhook_mode จาก Admin */
    public function test_index_exposes_admin_mock_modes(): void
    {
        Setting::set('chaos_mode', 'normal');
        Setting::set('webhook_mode', 'webhook_lost');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->getJson('/payments');

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
        ])->postJson('/payments', [
            'amount'       => 100,
            'method'       => 'card',
            'callback_url' => 'http://test/cb',
        ]);

        $response->assertStatus(401);
    }
}
