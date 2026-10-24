<?php

namespace Tests\Feature;

use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_details_show_transaction_id_without_merchant_references(): void
    {
        $payment = Payment::create([
            'amount'          => 1200,
            'currency'        => 'THB',
            'method'          => 'promptpay',
            'status'          => 'PENDING',
            'callback_url'    => 'https://concert.test/webhook',
            'idempotency_key' => 'DETAIL-KEY-101',
            'metadata'        => ['seat' => 'A1'],
        ]);

        $response = $this->withSession(['admin_authenticated' => true])
            ->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('🔍 ดูรายละเอียด')
            ->assertSee($payment->public_id)
            ->assertSee('DETAIL-KEY-101')
            ->assertDontSee('Booking Ref')
            ->assertDontSee('MERCHANT-DETAIL-101');
    }
}
