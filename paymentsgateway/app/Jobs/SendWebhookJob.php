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

        // webhook_replay: ส่ง timestamp เก่ากว่า 1 ชั่วโมง (ควรถูกปฏิเสธโดย receiver)
        $t = ($this->scenario === 'webhook_replay') ? (time() - 3600) : time();

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

            $delivery->increment('attempts');
            $delivery->update([
                'last_status_code' => $response->status(),
                'last_error'       => null,
            ]);

            if ($response->successful()) {
                $delivery->update(['delivered_at' => now()]);
                Log::info('Webhook delivered', [
                    'delivery_id' => $this->deliveryId,
                    'status'      => $response->status(),
                ]);
            } else {
                // ไม่ 2xx → queue จะ retry ตาม backoff
                $this->fail(new \Exception("Webhook receiver responded {$response->status()}"));
            }
        } catch (\Exception $e) {
            $delivery->increment('attempts');
            $delivery->update(['last_error' => $e->getMessage()]);
            Log::warning('Webhook delivery failed', [
                'delivery_id' => $this->deliveryId,
                'error'       => $e->getMessage(),
            ]);
            throw $e; // re-throw เพื่อให้ queue retry
        }
    }
}
