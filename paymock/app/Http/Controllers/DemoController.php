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
        $merchantPaymentId = (string) random_int(100000, 999999);
        $payment = $service->createOrFind([
            'payment_id'      => $merchantPaymentId,
            'amount'          => (float) $request->input('amount', 1500.00),
            'method'          => $request->input('method', 'promptpay'),
            'callback_url'    => url('/api/demo-webhook-sink'),
            'return_url'      => null,
            'expires_in'      => 600,
            'idempotency_key' => 'demo_key_' . $merchantPaymentId,
        ]);

        return response()->json([
            'ok'                      => true,
            'payment_id'              => $payment->merchantPaymentId(),
            'provider_transaction_id' => $payment->public_id,
            'checkout_url'            => route('checkout.show', $payment->public_id),
        ]);
    }
}
