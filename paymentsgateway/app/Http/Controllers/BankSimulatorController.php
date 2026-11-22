<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class BankSimulatorController extends Controller
{
    public function __construct(private readonly PaymentService $service) {}

    /**
     * GET /bank-simulate/{publicId}
     * หน้าจำลองแอปพลิเคชัน Mobile Banking ของธนาคาร (เช่น K-Plus, SCB Easy)
     */
    public function show(string $publicId)
    {
        $payment = Payment::where('public_id', $publicId)->firstOrFail();
        return view('bank.simulate', compact('payment'));
    }

    /**
     * POST /bank-simulate/{publicId}/pay
     * กดปุ่มยืนยันการโอนเงินในแอปธนาคาร
     */
    public function pay(string $publicId, Request $request)
    {
        $payment = Payment::where('public_id', $publicId)->firstOrFail();

        if (! $payment->isPending()) {
            return redirect()->route('bank.simulate.show', $publicId)
                ->with('status', 'already_processed');
        }

        // เปลี่ยนสถานะเป็น COMPLETED และยิง Webhook
        $this->service->transition($payment, 'COMPLETED');

        return redirect()->route('bank.simulate.show', $publicId)
            ->with('status', 'success');
    }
}
