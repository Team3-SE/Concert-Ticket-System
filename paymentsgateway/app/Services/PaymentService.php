<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(private readonly WebhookEmitter $webhooks) {}

    /**
     * สร้าง payment ใหม่ หรือคืนรายการเดิมถ้า idempotency_key ซ้ำ (idempotent)
     */
    public function createOrFind(array $data): Payment
    {
        $existing = Payment::where('idempotency_key', $data['idempotency_key'])->first();
        if ($existing) {
            return $existing;
        }

        $merchantPaymentId = (string) $data['payment_id'];

        return Payment::create([
            'payment_id'      => $merchantPaymentId,
            'order_ref'       => $data['booking_ref'] ?? $data['order_ref'] ?? $merchantPaymentId,
            'amount'          => $data['amount'],
            'currency'        => $data['currency'] ?? 'THB',
            'method'          => $data['method'],
            'status'          => 'PENDING',
            'callback_url'    => $data['callback_url'],
            'return_url'      => $data['return_url'] ?? null,
            'idempotency_key' => $data['idempotency_key'],
            'expires_at'      => now()->addSeconds($data['expires_in'] ?? config('paymock.default_expires_in')),
            'metadata'        => $data['metadata'] ?? null,
        ]);
    }

    /**
     * เปลี่ยนสถานะ payment และ emit webhook
     * ป้องกัน transition จาก non-PENDING
     */
    public function transition(Payment $payment, string $status, ?string $failureCode = null): void
    {
        if (! $payment->isPending()) {
            return; // already settled – idempotent
        }

        $payment->update([
            'status'       => $status,
            'failure_code' => $failureCode,
            'paid_at'      => $status === 'COMPLETED' ? now() : null,
        ]);

        $event = 'payment.' . strtolower($status);
        $this->webhooks->emit($payment->fresh(), $event);
    }

    public function cancel(Payment $payment): bool
    {
        if (! $payment->isPending()) {
            return false;
        }
        $this->transition($payment, 'FAILED', 'cancelled');
        return true;
    }
}
