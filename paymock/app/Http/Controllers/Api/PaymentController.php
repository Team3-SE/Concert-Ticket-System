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
            'chaos_mode' => \App\Models\Setting::get('chaos_mode', 'normal'),
            'recent'     => $recent,
        ]);
    }

    /**
     * POST /v1/payments
     * สร้างรายการจ่ายเงิน (idempotent ด้วย Idempotency-Key)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_ref'    => 'required|string|max:255',
            'amount'       => 'required|numeric|min:1',
            'method'       => 'required|in:card,promptpay',
            'callback_url' => 'required|url',
            'return_url'   => 'nullable|url',
            'expires_in'   => 'nullable|integer|min:60',
            'currency'     => 'nullable|string|size:3',
            'metadata'     => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => 'validation_error',
                'details' => $validator->errors(),
            ], 422);
        }

        $idempotencyKey = $request->header('Idempotency-Key') ?? $request->input('order_ref');
        $scenario       = $request->header('X-Mock-Scenario');

        $payment = $this->service->createOrFind(
            array_merge($validator->validated(), ['idempotency_key' => $idempotencyKey]),
            $scenario
        );

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
        return [
            'id'           => $payment->public_id,
            'order_ref'    => $payment->order_ref,
            'amount'       => (float) $payment->amount,
            'currency'     => $payment->currency,
            'method'       => $payment->method,
            'status'       => $payment->status,
            'failure_code' => $payment->failure_code,
            'checkout_url' => route('checkout.show', $payment->public_id),
            'return_url'   => $payment->return_url,
            'expires_at'   => $payment->expires_at?->toIso8601String(),
            'paid_at'      => $payment->paid_at?->toIso8601String(),
            'created_at'   => $payment->created_at?->toIso8601String(),
        ];
    }
}
