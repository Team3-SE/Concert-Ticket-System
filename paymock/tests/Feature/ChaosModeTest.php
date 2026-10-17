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

    /** Chaos: down → one customer-facing 503 response */
    public function test_chaos_down_returns_503(): void
    {
        Setting::set('chaos_mode', 'down');

        $this->postPayment()
            ->assertStatus(503)
            ->assertJson([
                'error' => 'service_unavailable',
                'message' => 'Gateway มีปัญหา โปรดลองอีกครั้ง',
            ]);
    }

    /** Chaos: rate limit remains available for API backoff testing. */
    public function test_chaos_rate_limit_returns_429_with_retry_after(): void
    {
        Setting::set('chaos_mode', 'rate_limit');

        $this->postPayment()
            ->assertStatus(429)
            ->assertHeader('Retry-After', '30');
    }

    /** Old saved simulations collapse into the one 503 gateway scenario. */
    public function test_retired_chaos_modes_return_the_same_503_response(): void
    {
        foreach (['timeout:5', 'bad_json', 'bad_auth'] as $oldMode) {
            Setting::set('chaos_mode', $oldMode);
            $this->postPayment()
                ->assertStatus(503)
                ->assertJsonPath('message', 'Gateway มีปัญหา โปรดลองอีกครั้ง');
        }
    }

    /** Chaos: system-wide setting works */
    public function test_system_wide_chaos_mode_works(): void
    {
        Setting::set('chaos_mode', 'down');
        $res = $this->postPayment(); // no X-Mock-Scenario header
        $res->assertStatus(503);
    }

    /** Request headers cannot change the system-wide business outage mode. */
    public function test_request_header_cannot_override_chaos_mode(): void
    {
        Setting::set('chaos_mode', 'down');
        $this->postPayment(['X-Mock-Scenario' => 'normal'])
            ->assertStatus(503);
    }
}
