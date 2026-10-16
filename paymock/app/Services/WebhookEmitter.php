<?php

namespace App\Services;

use App\Jobs\SendWebhookJob;
use App\Models\Payment;
use App\Models\WebhookDelivery;
use App\Support\MockMode;

class WebhookEmitter
{
    public function emit(Payment $payment, string $event): void
    {
        // Webhook behavior is configured globally in the authenticated Admin panel.
        // A payment never carries its own webhook override.
        $scenario = MockMode::webhookMode();
        [$sc, $arg] = array_pad(explode(':', (string) $scenario, 2), 2, null);

        // webhook_lost: ไม่ส่งเลย
        if ($sc === 'webhook_lost') {
            return;
        }

        // จำนวนสำเนาที่จะส่ง
        $copies = ($sc === 'webhook_dup') ? max(1, (int) ($arg ?: 3)) : 1;
        // delay ก่อนส่ง (วินาที)
        $delay  = ($sc === 'webhook_delay') ? (int) ($arg ?: 60) : 0;

        $payload = [
            'id'                      => $payment->public_id,
            'provider_transaction_id' => $payment->public_id,
            'payment_id'              => $payment->merchantPaymentId(),
            'event'                   => $event,
            'amount'                  => (float) $payment->amount,
            'currency'     => $payment->currency,
            'method'       => $payment->method,
            'status'       => $payment->status,
            'failure_code' => $payment->failure_code,
            'paid_at'      => $payment->paid_at?->toIso8601String(),
            'created_at'   => $payment->created_at?->toIso8601String(),
        ];

        for ($i = 0; $i < $copies; $i++) {
            $delivery = WebhookDelivery::create([
                'payment_id' => $payment->id,
                'event'      => $event,
                'payload'    => $payload,
            ]);

            SendWebhookJob::dispatch($delivery->id, $scenario)
                ->delay(now()->addSeconds($delay));
        }
    }
}
