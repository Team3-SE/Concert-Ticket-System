<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class DemoController extends Controller
{
    public function modal(Request $request)
    {
        return view('demo.modal');
    }

    public function createDemoPayment(Request $request, PaymentService $service)
    {
        $orderRef = 'DEMO-' . strtoupper(bin2hex(random_bytes(4)));
        $payment = $service->createOrFind([
            'order_ref'       => $orderRef,
            'amount'          => (float) $request->input('amount', 1500.00),
            'method'          => $request->input('method', 'promptpay'),
            'callback_url'    => url('/api/demo-webhook-sink'),
            'return_url'      => null, // รูปแบบ Modal ไม่ต้องใช้ return_url
            'expires_in'      => 600,
            'idempotency_key' => 'demo_key_' . $orderRef,
        ], $request->input('scenario'));

        return response()->json([
            'ok'           => true,
            'payment_id'   => $payment->public_id,
            'order_ref'    => $payment->order_ref,
            'checkout_url' => route('checkout.show', $payment->public_id),
        ]);
    }
}
