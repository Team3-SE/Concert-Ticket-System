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
        $chaosMode     = Setting::get('chaos_mode', 'normal');
        $deliveries    = WebhookDelivery::with('payment')->latest()->limit(50)->get();

        $stats = [
            'total'     => Payment::count(),
            'completed' => Payment::where('status', 'COMPLETED')->count(),
            'failed'    => Payment::where('status', 'FAILED')->count(),
            'pending'   => Payment::where('status', 'PENDING')->count(),
            'expired'   => Payment::where('status', 'EXPIRED')->count(),
        ];

        return view('admin.dashboard', compact('payments', 'chaosMode', 'deliveries', 'stats'));
    }

    /**
     * POST /admin/chaos
     * ตั้ง Chaos Mode ทั้งระบบ
     */
    public function setChaos(Request $request): RedirectResponse
    {
        $mode = $request->input('chaos_mode', 'normal');
        Setting::set('chaos_mode', $mode);
        return back()->with('success', "Chaos mode set to: {$mode}");
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
     * สร้าง payment จำลองตาม scenario เพื่อความสะดวกในการเดโม
     */
    public function runScenario(Request $request): RedirectResponse
    {
        $scenario = $request->input('scenario');
        $method   = $request->input('method', 'card');
        $amount   = (float) $request->input('amount', 1200.00);
        $orderRef = 'DEMO_' . strtoupper(substr(uniqid(), -6));

        $payment = $this->paymentService->createOrFind([
            'order_ref'       => $orderRef,
            'amount'          => $amount,
            'method'          => $method,
            'callback_url'    => env('DEFAULT_WEBHOOK_URL', 'http://concert_backend:5000/api/webhook'),
            'return_url'      => null,
            'idempotency_key' => $orderRef,
            'expires_in'      => 600,
        ], $scenario);

        return redirect()->route('checkout.show', $payment->public_id)
            ->with('info', "Created demo payment for scenario: {$scenario}");
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

        $body = json_encode([
            'id'           => 'TEST_' . strtoupper(substr(uniqid(), -8)),
            'event'        => $event,
            'order_ref'    => 'TEST_ORDER_001',
            'amount'       => 1200.00,
            'currency'     => 'THB',
            'method'       => 'card',
            'status'       => $status,
            'failure_code' => $failureCode,
            'paid_at'      => $paidAt,
            'created_at'   => now()->toIso8601String(),
        ]);

        $t      = time();
        $secret = config('paymock.webhook_secret');
        $sig    = hash_hmac('sha256', "$t.$body", $secret);

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders([
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
