<?php

/**
 * ReconcilePaymentsCommand
 * ตรวจรายการชำระเงินที่ค้าง PENDING กับ PayMock (กรณี webhook หาย/ล่าช้า) แล้วปรับสถานะ
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 20/11/2569
 * Updated : 20/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\PaymockService;
use Illuminate\Console\Command;

class ReconcilePaymentsCommand extends Command
{
    protected $signature = 'payments:reconcile {--minutes=1 : ตรวจเฉพาะรายการที่สร้างมาแล้วอย่างน้อยกี่นาที}';

    protected $description = 'ตรวจสถานะรายการชำระเงินที่ค้างรอยืนยันกับ PayMock';

    /**
     * handle(paymock)
     * param : paymock
     * return : int exit code
     */
    public function handle(PaymockService $paymock): int
    {
        if (! $paymock->enabled()) {
            return self::SUCCESS;
        }

        $payments = Payment::where('status', Payment::STATUS_PENDING)
            ->whereNotNull('provider_transaction_id')
            ->where('created_at', '<=', now()->subMinutes((int) $this->option('minutes')))
            ->where('created_at', '>=', now()->subDay())
            ->get();

        $summary = [];
        foreach ($payments as $payment) {
            try {
                $result = $paymock->syncPayment($payment, 'reconcile');
            } catch (\Throwable $e) {
                report($e);
                $result = 'error';
            }
            $summary[$result] = ($summary[$result] ?? 0) + 1;
        }

        $this->info('reconciled ' . $payments->count() . ' payment(s): ' . json_encode($summary));

        return self::SUCCESS;
    }
}
