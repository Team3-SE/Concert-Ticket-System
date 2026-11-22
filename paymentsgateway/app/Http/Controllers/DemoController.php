<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class DemoController extends Controller
{
    public function modal(Request $request)
    {
        return view('demo.modal', ['bookingPayment' => null]);
    }

    /** Return page after hosted checkout: read the latest booking/payment record from DB. */
    public function booking(string $publicId)
    {
        $bookingPayment = Payment::where('public_id', $publicId)->firstOrFail();

        return view('demo.modal', compact('bookingPayment'));
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

        $returnUrl = route('demo.booking', $payment->public_id);
        $returnPath = '/demo/bookings/' . $payment->public_id;
        $payment->update(['return_url' => $returnUrl]);

        return response()->json([
            'ok'                      => true,
            'payment_id'              => $payment->merchantPaymentId(),
            'checkout_url'            => route('checkout.show', $payment->public_id),
            'redirect_url'            => route('checkout.show', $payment->public_id),
            'return_path'             => $returnPath,
        ]);
    }
}
