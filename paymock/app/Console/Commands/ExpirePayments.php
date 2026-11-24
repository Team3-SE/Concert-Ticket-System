<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Console\Command;

class ExpirePayments extends Command
{
    protected $signature   = 'payments:expire';
    protected $description = 'Expire PENDING payments that have passed their expires_at';

    public function __construct(private readonly PaymentService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $count = 0;
        Payment::where('status', 'PENDING')
            ->where('expires_at', '<', now())
            ->each(function (Payment $payment) use (&$count) {
                $this->service->transition($payment, 'EXPIRED');
                $count++;
            });

        if ($count > 0) {
            $this->info("Expired {$count} payment(s).");
        }
        return self::SUCCESS;
    }
}
