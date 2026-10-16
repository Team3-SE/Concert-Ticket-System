<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(private readonly PaymentService $service) {}

    /**
     * GET /checkout/{publicId}
     * หน้าจ่ายเงินจำลอง
     */
    public function show(string $publicId): View|RedirectResponse
    {
        $payment = Payment::where('public_id', $publicId)->firstOrFail();

        if (! $payment->isPending()) {
            return view('checkout.done', compact('payment'));
        }

        if ($payment->expires_at && $payment->expires_at->isPast()) {
            $this->service->transition($payment, 'EXPIRED');
            return view('checkout.done', compact('payment'));
        }

        // Generate PromptPay QR payload (simplified)
        $qrPayload = null;
        if ($payment->method === 'promptpay') {
            // PromptPay payload format: phone/tax ID based
            $qrPayload = $this->generatePromptPayPayload((float) $payment->amount);
        }

        return view('checkout.show', compact('payment', 'qrPayload'));
    }

    /**
     * POST /checkout/{publicId}/card
     * รับเลขบัตรทดสอบ
     */
    public function processCard(string $publicId, Request $request): RedirectResponse
    {
        $payment = Payment::where('public_id', $publicId)->firstOrFail();

        if (! $payment->isPending()) {
            return redirect()->route('checkout.show', $publicId)
                ->with('error', 'Payment is no longer pending.');
        }

        $cardNumber = preg_replace('/\D/', '', $request->input('card_number', ''));
        $rules      = config('paymock.card_rules', []);
        $rule       = $rules[$cardNumber] ?? ['FAILED', 'card_declined'];

        [$status, $failureCode] = $rule;

        // 3-D Secure card: stay PENDING, show OTP page
        if ($failureCode === '3ds_required') {
            return redirect()->route('checkout.show', $publicId)
                ->with('info', '3D Secure verification required. Use OTP: 123456');
        }

        $this->service->transition($payment, $status, $failureCode);

        // Stay on PayMock receipt page by default to display transaction status & receipt
        if ($request->boolean('redirect_to_return') && $payment->return_url) {
            return redirect($payment->return_url . '?payment_id=' . $payment->public_id);
        }

        return redirect()->route('checkout.show', $publicId);
    }

    /**
     * POST /checkout/{publicId}/promptpay
     * ผู้ใช้กดปุ่ม "จำลองจ่ายแล้ว" (PromptPay)
     */
    public function processPromptPay(string $publicId, Request $request): RedirectResponse
    {
        $payment = Payment::where('public_id', $publicId)->firstOrFail();

        if (! $payment->isPending()) {
            return redirect()->route('checkout.show', $publicId)
                ->with('error', 'Payment is no longer pending.');
        }

        $action = $request->input('action', 'pay');

        if ($action === 'fail') {
            $this->service->transition($payment, 'FAILED', 'payment_declined');
        } else {
            $this->service->transition($payment, 'COMPLETED');
        }

        if ($request->boolean('redirect_to_return') && $payment->return_url) {
            return redirect($payment->return_url . '?payment_id=' . $payment->public_id);
        }

        return redirect()->route('checkout.show', $publicId);
    }

    /**
     * POST /checkout/{publicId}/simulate
     * API สำหรับจำลองสถานะการชำระเงิน (COMPLETED / FAILED)
     */
    public function simulate(string $publicId, Request $request): \Illuminate\Http\JsonResponse
    {
        $payment = Payment::where('public_id', $publicId)->firstOrFail();

        if (! $payment->isPending()) {
            return response()->json(['ok' => false, 'message' => 'Not pending']);
        }

        $status      = $request->input('status', 'COMPLETED');
        $failureCode = $request->input('failure_code');

        $this->service->transition($payment, $status, $failureCode);

        return response()->json(['ok' => true, 'status' => $status]);
    }

    private function generatePromptPayPayload(float $amount): string
    {
        // สร้าง EMV QR payload พื้นฐาน (ใช้เบอร์โทรศัพทร์สาธิต)
        $phone       = '0812345678'; // demo phone
        $amountStr   = number_format($amount, 2, '.', '');
        $phoneFormatted = '0066' . ltrim($phone, '0');

        // EMV QR Code Specification for PromptPay
        $payload  = '000201';                  // Payload Format Indicator
        $payload .= '010212';                  // Point of Initiation Method
        $ppPayload = '0016A000000677010111'    // Application identifier
                   . '01' . sprintf('%02d', strlen($phoneFormatted)) . $phoneFormatted; // Account
        $payload .= '29' . sprintf('%02d', strlen($ppPayload)) . $ppPayload;
        $payload .= '5303764';                 // Transaction Currency (THB=764)
        $payload .= '54' . sprintf('%02d', strlen($amountStr)) . $amountStr; // Amount
        $payload .= '5802TH';                  // Country code
        $payload .= '6304';                    // CRC placeholder

        // CRC16-CCITT
        $crc = $this->crc16($payload);
        return $payload . $crc;
    }

    private function crc16(string $data): string
    {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= ord($data[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x1021;
                } else {
                    $crc <<= 1;
                }
                $crc &= 0xFFFF;
            }
        }
        return strtoupper(sprintf('%04X', $crc));
    }
}
