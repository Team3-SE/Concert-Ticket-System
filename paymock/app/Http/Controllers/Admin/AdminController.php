<?php

namespace App\Http\Controllers\Admin;

use App\Models\Payment;
use App\Models\Setting;
use App\Models\WebhookDelivery;
use App\Services\PaymentService;
use App\Services\WebhookEmitter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly WebhookEmitter $emitter,
    ) {}

    public function loginForm(): View
    {
        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $password = $request->input('password');
        if (hash_equals(config('paymock.admin_password'), $password)) {
            $request->session()->put('admin_authenticated', true);
            return redirect()->route('admin.dashboard');
        }
        return back()->withErrors(['password' => 'Invalid password']);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('admin_authenticated');
        return redirect()->route('admin.login');
    }

    public function dashboard(Request $request): View
    {
        $payments      = Payment::with('webhookDeliveries')
            ->latest()->paginate(20);
        $chaosMode     = \App\Support\MockMode::chaosMode();
        $webhookMode   = \App\Support\MockMode::webhookMode();
        $deliveries    = WebhookDelivery::with('payment')->latest()->limit(50)->get();

        $stats = [
            'total'     => Payment::count(),
            'completed' => Payment::where('status', 'COMPLETED')->count(),
            'failed'    => Payment::where('status', 'FAILED')->count(),
            'pending'   => Payment::where('status', 'PENDING')->count(),
            'expired'   => Payment::where('status', 'EXPIRED')->count(),
        ];

        return view('admin.dashboard', compact('payments', 'chaosMode', 'webhookMode', 'deliveries', 'stats'));
    }

    /**
     * POST /admin/chaos
     * ตั้ง Chaos Mode ทั้งระบบ
     */
    public function setChaos(Request $request): RedirectResponse
    {
        $mode = $request->input('chaos_mode', 'normal');
        if (in_array($mode, ['slow', 'timeout'], true)) {
            // Both delay modes are configurable from Admin. The cap prevents a
            // typo from blocking a PHP worker indefinitely.
            $seconds = max(0, min(3600, (int) $request->input('delay_seconds', 5)));
            $mode .= ':' . $seconds;
        }
        if (! \App\Support\MockMode::isChaos($mode)) {
            $mode = 'normal';
        }
        Setting::set('chaos_mode', $mode);
        return back()->with('success', "Chaos mode set to: {$mode}");
    }

    /**
     * POST /admin/webhook-mode
     * ตั้ง Webhook anomaly ทั้งระบบ (ชั้นรองจาก Chaos)
     */
    public function setWebhookMode(Request $request): RedirectResponse
    {
        $mode = $request->input('webhook_mode', 'normal');
        if ($mode !== 'normal' && ! \App\Support\MockMode::isWebhook($mode)) {
            $mode = 'normal';
        }
        Setting::set('webhook_mode', $mode);
        return back()->with('success', "Webhook mode set to: {$mode}");
    }

    /**
     * POST /admin/payments/{publicId}/transition
     * บังคับเปลี่ยนสถานะ payment
     */
    public function transition(string $publicId, Request $request): RedirectResponse
    {
        $payment     = Payment::where('public_id', $publicId)->firstOrFail();
        $status      = $request->input('status');
        $failureCode = $request->input('failure_code');

        if (! in_array($status, ['COMPLETED', 'FAILED', 'EXPIRED'])) {
            return back()->withErrors(['status' => 'Invalid status']);
        }

        $this->paymentService->transition($payment, $status, $failureCode);

        return back()->with('success', "Payment {$publicId} transitioned to {$status}");
    }

    /**
     * POST /admin/payments/{publicId}/resend-webhook
     * ส่ง webhook ซ้ำ (แต่ใช้ event_id เดิม ระบบหลักต้อง idempotent)
     */
    public function resendWebhook(string $publicId, Request $request): RedirectResponse
    {
        $payment = Payment::where('public_id', $publicId)->firstOrFail();
        $event   = 'payment.' . strtolower($payment->status);

        $this->emitter->emit($payment->fresh(), $event);

        return back()->with('success', "Webhook re-queued for {$publicId}");
    }

    /**
     * POST /admin/scenarios/run
     * สร้าง payment จำลองเพื่อความสะดวกในการเดโม
     */
    public function runScenario(Request $request): RedirectResponse
    {
        $method     = $request->input('method', 'card');
        $cardNumber = $request->input('card_number');
        $amount     = (float) $request->input('amount', 1200.00);
        $merchantPaymentId = (string) random_int(100000, 999999);

        $payment = $this->paymentService->createOrFind([
            'payment_id'      => $merchantPaymentId,
            'amount'          => $amount,
            'method'          => $method,
            'callback_url'    => env('DEFAULT_WEBHOOK_URL', 'http://concert_backend:5000/api/webhook'),
            'return_url'      => null,
            'idempotency_key' => 'DEMO_' . $merchantPaymentId,
            'expires_in'      => 600,
        ]);

        $routeParams = ['publicId' => $payment->public_id];
        if ($cardNumber) {
            $routeParams['card'] = $cardNumber;
        }

        return redirect()->route('checkout.show', $routeParams)
            ->with('info', "Created demo payment. Webhook behavior follows the current Admin Webhook Mode.");
    }

    /**
     * POST /admin/clear-all
     * ลบข้อมูล payment และ webhook deliveries ทั้งหมด (reset สำหรับเดโม)
     */
    public function clearAll(Request $request): RedirectResponse
    {
        \DB::statement('DELETE FROM webhook_deliveries');
        \DB::statement('DELETE FROM payments');
        \DB::statement('DELETE FROM jobs');

        return back()->with('success', 'ล้างข้อมูลทั้งหมดแล้ว (Payments + Webhooks + Jobs)');
    }

    /**
     * POST /admin/webhook-test
     * ยิง test webhook ไปที่ URL ที่กำหนด พร้อม signature จริง
     */
    public function webhookTest(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'url'   => 'required|url',
            'event' => 'required|string',
        ]);

        $event = $request->input('event', 'payment.completed');
        $status = match($event) {
            'payment.failed'  => 'FAILED',
            'payment.expired' => 'EXPIRED',
            default           => 'COMPLETED',
        };
        $failureCode = ($event === 'payment.failed') ? 'card_declined' : null;
        $paidAt      = ($status === 'COMPLETED') ? now()->toIso8601String() : null;

        $txnId = 'txn_TEST_' . strtoupper(substr(uniqid(), -8));
        $body = json_encode([
            'id'           => $txnId,
            'payment_id'   => '42',
            'event'                   => $event,
            'amount'       => 1200.00,
            'currency'     => 'THB',
            'method'       => 'card',
            'status'       => $status,
            'failure_code' => $failureCode,
            'paid_at'      => $paidAt,
            'created_at'   => now()->toIso8601String(),
        ]);

        $scenario = $request->input('scenario', 'normal');
        $t        = ($scenario === 'webhook_replay') ? (time() - 3600) : time();
        $secret   = config('paymock.webhook_secret');
        $sig      = hash_hmac('sha256', "$t.$body", $secret);

        if ($scenario === 'webhook_bad_sig') {
            $sig = str_repeat('0', 64);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Signature'  => "t={$t},v1={$sig}",
                    'X-Event-Id'   => 'TEST_' . strtoupper(substr(uniqid(), -8)),
                ])
                ->withBody($body, 'application/json')
                ->post($request->input('url'));

            return response()->json([
                'ok'         => $response->successful(),
                'status'     => $response->status(),
                'body'       => $response->body(),
                'signature'  => "t={$t},v1={$sig}",
                'raw_body'   => $body,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok'    => false,
                'error' => $e->getMessage(),
            ], 200);
        }
    }
}
