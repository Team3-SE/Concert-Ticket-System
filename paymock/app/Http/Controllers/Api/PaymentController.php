<?php

namespace App\Http\Controllers\Api;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $service) {}

    /**
     * GET /v1/payments
     * ดูสถานะบริการและรายการล่าสุด
     */
    public function index(): JsonResponse
    {
        $recent = Payment::latest()->limit(10)->get()->map(fn ($p) => $this->formatPayment($p));
        return response()->json([
            'service'    => 'PayMock Gateway API',
            'status'     => 'online',
            'chaos_mode'   => \App\Support\MockMode::chaosMode(),
            'webhook_mode' => \App\Support\MockMode::webhookMode(),
            'recent'     => $recent,
        ]);
    }

    /**
     * POST /v1/payments
     * สร้างรายการจ่ายเงิน (idempotent ด้วย Idempotency-Key)
     * รองรับ PromptPay (ส่งกลับ qrcode_url) และ Card โดยตรง (ไม่ต้องผ่าน checkout_url)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_id'   => 'required|string|max:255',
            'amount'       => 'required|numeric|min:1',
            'method'       => 'required|in:card,promptpay',
            'callback_url' => 'required|url',
            'expires_in'   => 'nullable|integer|min:60',
            'currency'     => 'nullable|string|size:3',
            'metadata'     => 'nullable|array',
            'card_number'  => 'nullable|string',
            'exp_month'    => 'nullable|string',
            'exp_year'     => 'nullable|string',
            'cvc'          => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => 'validation_error',
                'details' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $merchantPaymentId = (string) $validated['payment_id'];
        $idempotencyKey = $request->header('Idempotency-Key') ?? $merchantPaymentId;

        $payment = $this->service->createOrFind(
            array_merge($validated, ['idempotency_key' => $idempotencyKey])
        );

        // ถ้าจ่ายผ่านบัตรเครดิตโดยตรง และส่งเลขบัตรมา ให้ประมวลผลทันที
        if ($payment->isPending() && $payment->method === 'card' && $request->filled('card_number')) {
            $cardNumber = preg_replace('/\D/', '', $request->input('card_number', ''));
            $rules      = config('paymock.card_rules', []);
            $rule       = $rules[$cardNumber] ?? ['FAILED', 'card_declined'];
            [$status, $failureCode] = $rule;

            $this->service->transition($payment, $status, $failureCode);
            $payment->refresh();
        }

        return response()->json($this->formatPayment($payment), 201);
    }

    /**
     * GET /v1/payments/{publicId}
     * ดูสถานะ – ใช้ polling/reconcile
     */
    public function show(string $publicId): JsonResponse
    {
        $payment = Payment::where('public_id', $publicId)->firstOrFail();
        return response()->json($this->formatPayment($payment));
    }

    /**
     * GET /v1/payments/{publicId}/qr.svg
     * ส่งคืนไฟล์ภาพ SVG QR Code สำหรับ PromptPay
     */
    public function qrSvg(string $publicId): \Symfony\Component\HttpFoundation\Response
    {
        $payment = Payment::where('public_id', $publicId)->firstOrFail();
        $bankSimulateUrl = url("/bank-simulate/{$payment->public_id}");
        $svg = \App\Services\QrService::generateSvg($bankSimulateUrl, 260);

        return response($svg, 200, [
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * POST /v1/payments/{publicId}/cancel
     */
    public function cancel(string $publicId): JsonResponse
    {
        $payment = Payment::where('public_id', $publicId)->firstOrFail();

        if (! $this->service->cancel($payment)) {
            return response()->json([
                'error'   => 'cannot_cancel',
                'message' => 'Payment is already settled and cannot be cancelled.',
            ], 409);
        }

        return response()->json(['ok' => true, 'status' => 'FAILED', 'failure_code' => 'cancelled']);
    }

    private function formatPayment(Payment $payment): array
    {
        $data = [
            'id'                       => $payment->public_id,
            'provider_transaction_id'  => $payment->public_id,
            'payment_id'               => $payment->merchantPaymentId(),
            'amount'       => (float) $payment->amount,
            'currency'     => $payment->currency,
            'method'       => $payment->method,
            'status'       => $payment->status,
            'failure_code' => $payment->failure_code,
            'expires_at'   => $payment->expires_at?->toIso8601String(),
            'paid_at'      => $payment->paid_at?->toIso8601String(),
            'created_at'   => $payment->created_at?->toIso8601String(),
        ];

        // สำหรับ PromptPay ส่งคืน URL แสดงภาพ QR Code และ URL จำลองแอปธนาคาร
        if ($payment->method === 'promptpay') {
            $data['qrcode_url']         = url("/v1/payments/{$payment->public_id}/qr.svg");
            $data['bank_simulator_url'] = url("/bank-simulate/{$payment->public_id}");
        }

        return $data;
    }
}
