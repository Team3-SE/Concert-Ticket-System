<?php

namespace App\Http\Controllers\Api;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $service) {}

    /**
     * GET /payments
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
     * POST /payments
     * สร้างรายการจ่ายเงิน (idempotent ด้วย Idempotency-Key)
     * Card จะสร้างเป็น PENDING แล้วให้ redirect ไปยัง hosted checkout เพื่อกรอกบัตร
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount'       => 'required|numeric|min:1',
            'method'       => 'required|in:card,promptpay',
            'callback_url' => 'required|url',
            'return_url'   => 'nullable|url',
            'expires_in'   => 'nullable|integer|min:60',
            'currency'     => 'nullable|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => 'validation_error',
                'details' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated['metadata'] = $request->input('metadata');

        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        if ($idempotencyKey === '') {
            $idempotencyKey = (string) Str::uuid();
        }

        $payment = $this->service->createOrFind(
            array_merge($validated, ['idempotency_key' => $idempotencyKey])
        );

        return response()->json($this->formatPayment($payment), 201);
    }

    /**
     * GET /payments/{publicId}
     * ดูสถานะ – ใช้ polling/reconcile
     */
    public function show(string $publicId): JsonResponse
    {
        $payment = Payment::where('public_id', $publicId)->firstOrFail();
        return response()->json($this->formatPayment($payment));
    }

    /**
     * GET /payments/{publicId}/qr.svg
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
     * POST /payments/{publicId}/cancel
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
            // `id` is the canonical PayMock provider transaction ID. Do not
            // return a second alias for it; use this value for GET, cancel,
            // QR, polling and reconciliation.
            'id'           => $payment->public_id,
            'amount'       => (float) $payment->amount,
            'currency'     => $payment->currency,
            'method'       => $payment->method,
            'status'       => $payment->status,
            'failure_code' => $payment->failure_code,
            'expires_at'   => $payment->expires_at?->toIso8601String(),
            'paid_at'      => $payment->paid_at?->toIso8601String(),
            'created_at'   => $payment->created_at?->toIso8601String(),
            'return_url'   => $payment->return_url,
        ];

        $metadata = $payment->responseMetadata();
        if ($metadata !== null) {
            $data['metadata'] = $metadata;
        }

        if ($payment->method === 'promptpay') {
            $data['qrcode_url']         = url("/payments/{$payment->public_id}/qr.svg");
            $data['bank_simulator_url'] = url("/bank-simulate/{$payment->public_id}");
        } elseif ($payment->method === 'card') {
            $data['checkout_url'] = url("/checkout/{$payment->public_id}");
        }

        return $data;
    }
}
