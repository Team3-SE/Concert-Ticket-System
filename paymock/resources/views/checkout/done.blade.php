@extends('layouts.app')
@section('title', 'Payment ' . $payment->status)

@section('content')
<div class="bg-white rounded-xl shadow-lg overflow-hidden border-t-4 {{ match($payment->status) { 'COMPLETED' => 'border-green-600', 'FAILED' => 'border-red-600', default => 'border-yellow-600' } }}">
    <div class="px-6 py-8 text-center">
        @if($payment->status === 'COMPLETED')
            <div class="text-6xl mb-3">✅</div>
            <h1 class="text-2xl font-bold text-green-700">ชำระเงินสำเร็จ (COMPLETED)</h1>
            <p class="text-gray-500 mt-1">ยอดเงิน ฿{{ number_format($payment->amount, 2) }} ได้รับการประมวลผลแล้ว</p>
        @elseif($payment->status === 'FAILED')
            <div class="text-6xl mb-3">❌</div>
            <h1 class="text-2xl font-bold text-red-700">การชำระเงินไม่สำเร็จ (FAILED)</h1>
            <p class="text-gray-500 mt-1">
                รหัสข้อผิดพลาด: <span class="font-mono font-bold text-red-600">{{ $payment->failure_code ?? 'declined' }}</span>
            </p>
        @else
            <div class="text-6xl mb-3">⏰</div>
            <h1 class="text-2xl font-bold text-yellow-700">รายการหมดอายุ (EXPIRED)</h1>
            <p class="text-gray-500 mt-1">หมดเวลาการชำระเงินสำหรับรายการนี้แล้ว</p>
        @endif

        <!-- Transaction Details Box -->
        <div class="mt-6 p-4 bg-gray-50 rounded-xl text-sm text-left space-y-2 border border-gray-100">
            <div class="flex justify-between">
                <span class="text-gray-500">Provider Txn:</span>
                <span class="font-mono font-bold text-indigo-700">{{ $payment->public_id }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Payment ID:</span>
                <span class="font-mono">{{ $payment->payment_id ?? $payment->order_ref }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">ยอดชำระ:</span>
                <span class="font-bold text-gray-800">฿{{ number_format($payment->amount, 2) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-500">สถานะรายการ:</span>
                <span class="px-2 py-0.5 rounded text-xs font-bold {{ match($payment->status) {
                    'COMPLETED' => 'bg-green-100 text-green-700',
                    'FAILED'    => 'bg-red-100 text-red-700',
                    default     => 'bg-yellow-100 text-yellow-700'
                } }}">
                    {{ $payment->status }}
                </span>
            </div>
            @if($payment->webhookScenario())
            <div class="flex justify-between">
                <span class="text-gray-500">Scenario จำลอง:</span>
                <span class="font-mono text-xs bg-gray-200 px-1.5 py-0.5 rounded text-gray-800">{{ $payment->webhookScenario() }}</span>
            </div>
            @endif
            @if($payment->paid_at)
            <div class="flex justify-between">
                <span class="text-gray-500">เวลาที่ชำระ:</span>
                <span class="text-gray-700">{{ $payment->paid_at->format('d M Y H:i:s') }}</span>
            </div>
            @endif
        </div>

        <!-- Webhook Status Tracker -->
        @php
            $deliveries = $payment->webhookDeliveries()->latest()->get();
        @endphp
        <div class="mt-4 p-4 bg-indigo-50/50 rounded-xl text-left border border-indigo-100 text-xs space-y-2">
            <div class="font-bold text-indigo-900 flex items-center justify-between">
                <span>📡 สถานะการส่ง Webhook ไปยังระบบหลัก</span>
                <span class="font-normal text-gray-500">{{ $deliveries->count() }} ครั้ง</span>
            </div>
            @forelse($deliveries as $d)
                <div class="bg-white p-2.5 rounded-lg border border-indigo-100 flex items-center justify-between font-mono text-[11px]">
                    <div>
                        <span class="font-bold text-gray-800">{{ $d->event }}</span>
                        <span class="text-gray-400">({{ substr($d->event_id, 0, 14) }}...)</span>
                    </div>
                    <div>
                        @if($d->delivered_at)
                            <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded font-bold">ส่งแล้ว (HTTP {{ $d->last_status_code ?? 200 }})</span>
                        @elseif($d->last_error)
                            <span class="bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded" title="{{ $d->last_error }}">
                                รอ Queue Retry (ระบบหลักยังไม่ตอบ)
                            </span>
                        @else
                            <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded">กำลังส่งผ่าน Queue...</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-[11px] italic">
                    {{ $payment->webhookScenario() === 'webhook_lost' ? '⛔ จำลอง Webhook Lost: ไม่ส่ง Webhook ตาม scenario' : 'ยังไม่มีการส่ง Webhook' }}
                </p>
            @endforelse
        </div>

        <!-- Action Buttons -->
        <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center items-center">
            <!-- ปุ่มปิดหน้าต่าง (แสดงเฉพาะเมื่อเปิดใน iframe Modal) -->
            <div id="iframeCloseBtnWrapper" style="display: none;" class="w-full sm:w-auto">
                <button type="button"
                        onclick="window.parent.postMessage({ type: 'paymock:close_modal', payment_id: '{{ $payment->public_id }}', status: '{{ $payment->status }}' }, '*')"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-6 rounded-lg text-sm transition shadow cursor-pointer">
                    ✕ ปิดหน้าต่างนี้
                </button>
            </div>

            <a href="{{ route('admin.dashboard') }}"
               class="w-full sm:w-auto bg-gray-800 hover:bg-gray-900 text-white font-bold py-2.5 px-6 rounded-lg text-sm transition shadow">
                ⚙️ กลับหน้า Admin Dashboard
            </a>

            @if($payment->return_url)
            <div class="w-full sm:w-auto">
                <a href="{{ $payment->return_url }}?payment_id={{ $payment->public_id }}"
                   target="_top"
                   class="w-full sm:w-auto inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-lg text-sm transition shadow">
                    🔗 กลับไปยังระบบคอนเสิร์ต
                </a>
            </div>
            @endif
        </div>
        @if($payment->return_url)
            <p class="text-[11px] text-gray-400 mt-2">
                * ปุ่มกลับระบบคอนเสิร์ตจะเปิดหน้า <code class="bg-gray-100 px-1 rounded">{{ $payment->return_url }}</code> (ต้องรันระบบคอนเสิร์ตไว้จึงจะไม่ขึ้น 404)
            </p>
        @endif
    </div>
</div>

<script>
// ส่ง event postMessage ไปยัง Parent Window อัตโนมัติเมื่อสถานะเปลี่ยนสำเร็จ
document.addEventListener('DOMContentLoaded', function() {
    if (window.parent && window.parent !== window) {
        // แสดงปุ่มปิด Modal
        var closeBtn = document.getElementById('iframeCloseBtnWrapper');
        if (closeBtn) closeBtn.style.display = 'block';

        // แจ้ง Parent Window
        window.parent.postMessage({
            type: 'paymock:payment_result',
            status: @json($payment->status),
            payment_id: @json($payment->payment_id),
            provider_transaction_id: @json($payment->public_id),
            amount: {{ (float) $payment->amount }}
        }, '*');
    }
});
</script>
@endsection
