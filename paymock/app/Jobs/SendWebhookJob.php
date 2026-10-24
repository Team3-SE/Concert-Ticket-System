<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;
    public int $timeout = 30;

    public function __construct(
        private readonly int $deliveryId,
        private readonly ?string $scenario = null,
    ) {}

    public function backoff(): array
    {
        return [5, 30, 120, 300, 600];
    }

    public function handle(): void
    {
        $delivery = WebhookDelivery::with('payment')->findOrFail($this->deliveryId);
        $payment  = $delivery->payment;

        $body = json_encode($delivery->payload);

        $t = time();

        // คำนวณ signature
        $secret = config('paymock.webhook_secret');
        $sig    = hash_hmac('sha256', "$t.$body", $secret);

        // webhook_bad_sig: ส่ง signature ผิดจงใจ
        if ($this->scenario === 'webhook_bad_sig') {
            $sig = str_repeat('0', 64);
        }

        try {
            // No fixed client timeout: webhook receivers choose their own
            // processing window, while queue retries handle failed delivery.
            $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Signature'  => "t={$t},v1={$sig}",
                    'X-Event-Id'   => $delivery->event_id,
                ])
                ->withBody($body, 'application/json')
                ->post($payment->callback_url);
        } catch (\Exception $e) {
            $delivery->increment('attempts');
            $delivery->update([
                'last_status_code' => null,
                'last_error'       => $e->getMessage(),
            ]);
            Log::warning('Webhook delivery failed', [
                'delivery_id' => $this->deliveryId,
                'error'       => $e->getMessage(),
            ]);
            throw $e;
        }

        $delivery->increment('attempts');
        $delivery->update([
            'last_status_code' => $response->status(),
            'last_error'       => $response->successful()
                ? null
                : "Webhook receiver responded with HTTP {$response->status()}",
        ]);

        if ($response->successful()) {
            $delivery->update(['delivered_at' => now()]);
            Log::info('Webhook delivered', [
                'delivery_id' => $this->deliveryId,
                'status'      => $response->status(),
            ]);
            return;
        }

        Log::warning('Webhook receiver returned a non-2xx response', [
            'delivery_id' => $this->deliveryId,
            'status'      => $response->status(),
        ]);

        throw new RuntimeException("Webhook receiver responded with HTTP {$response->status()}");
    }
}
