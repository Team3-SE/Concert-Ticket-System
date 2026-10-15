<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChaosModeTest extends TestCase
{
    use RefreshDatabase;

    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = config('paymock.api_key');
    }

    private function postPayment(array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders(array_merge([
            'Authorization'   => 'Bearer ' . $this->apiKey,
            'Idempotency-Key' => 'KEY_' . uniqid(),
        ], $headers))->postJson('/v1/payments', [
            'payment_id'   => (string) random_int(1000, 999999),
            'amount'       => 1000,
            'method'       => 'card',
            'callback_url' => 'http://test/cb',
        ]);
    }

    /** Chaos: down → 503 */
    public function test_chaos_down_returns_503(): void
    {
        $res = $this->postPayment(['X-Mock-Scenario' => 'down']);
        $res->assertStatus(503)->assertJson(['error' => 'service_unavailable']);
    }

    /** Chaos: rate_limit → 429 + Retry-After */
    public function test_chaos_rate_limit_returns_429_with_header(): void
    {
        $res = $this->postPayment(['X-Mock-Scenario' => 'rate_limit']);
        $res->assertStatus(429)
            ->assertHeader('Retry-After', '30');
    }

    /** Chaos: bad_auth → 401 */
    public function test_chaos_bad_auth_returns_401(): void
    {
        $res = $this->postPayment(['X-Mock-Scenario' => 'bad_auth']);
        $res->assertStatus(401)->assertJson(['error' => 'invalid_api_key']);
    }

    /** Chaos: bad_json → 502 with HTML body */
    public function test_chaos_bad_json_returns_502_html(): void
    {
        $res = $this->postPayment(['X-Mock-Scenario' => 'bad_json']);
        $res->assertStatus(502);
        $this->assertStringContainsString('Bad Gateway', $res->getContent());
    }

    /** Chaos: System-wide setting works when header not present */
    public function test_system_wide_chaos_mode_works(): void
    {
        Setting::set('chaos_mode', 'down');
        $res = $this->postPayment(); // no X-Mock-Scenario header
        $res->assertStatus(503);
    }

    /** Chaos: Header overrides system-wide setting */
    public function test_header_overrides_system_chaos_mode(): void
    {
        Setting::set('chaos_mode', 'down');
        // normal override
        $res = $this->postPayment(['X-Mock-Scenario' => 'normal']);
        $res->assertStatus(201);
    }

    /** Merchant ส่ง webhook_* ใน header ไม่มีผล — ต้องตั้งที่ Admin */
    public function test_webhook_header_does_not_apply_chaos_or_stamp_scenario(): void
    {
        $res = $this->postPayment(['X-Mock-Scenario' => 'webhook_lost']);
        $res->assertStatus(201);
        $this->assertDatabaseHas('payments', [
            'public_id' => $res->json('id'),
            'scenario'  => null,
        ]);
    }
}
