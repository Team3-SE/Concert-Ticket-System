@extends('layouts.app')
@section('title', 'Checkout')

@section('content')
<div class="bg-white rounded-xl shadow-lg overflow-hidden" x-data="checkoutPage()">
    <!-- Header -->
    <div class="bg-indigo-600 text-white px-6 py-4">
        <div class="flex justify-between items-center">
            <h1 class="text-xl font-bold">💳 PayMock Checkout</h1>
            <div class="text-right">
                <div class="text-sm opacity-80">Amount</div>
                <div class="text-2xl font-bold">฿{{ number_format($payment->amount, 2) }}</div>
            </div>
        </div>
        <div class="mt-2 text-sm opacity-70">
            Order: <span class="font-mono font-semibold">{{ $payment->order_ref }}</span> &nbsp;|&nbsp;
            ID: <span class="font-mono">{{ $payment->public_id }}</span>
            @if($payment->scenario)
                &nbsp;|&nbsp; Scenario: <span class="bg-indigo-800 px-2 py-0.5 rounded font-mono text-xs">{{ $payment->scenario }}</span>
            @endif
        </div>
    </div>

    <!-- Countdown Timer -->
    <div class="bg-yellow-50 border-b border-yellow-200 px-6 py-3"
         x-data="countdownTimer('{{ $payment->expires_at?->toIso8601String() }}')"
         x-init="start()">
        <div class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-2">
                <span class="text-yellow-700">⏱ Session expires in:</span>
                <span class="font-mono font-bold text-yellow-900" x-text="display" x-cloak>--:--</span>
            </div>
            <div class="text-xs text-yellow-600">
                Idempotency-Key Protected
            </div>
        </div>
    </div>

    <!-- Auto-Pay Scenario Banner (e.g. auto_pay:15) -->
    @php
        $autoPaySeconds = null;
        if ($payment->scenario && str_starts_with($payment->scenario, 'auto_pay:')) {
            $autoPaySeconds = (int) explode(':', $payment->scenario)[1];
        }
    @endphp

    @if($autoPaySeconds)
    <div class="bg-blue-50 border-b border-blue-200 px-6 py-3 text-sm flex items-center justify-between"
         x-data="autoPayTimer({{ $autoPaySeconds }}, '{{ route('checkout.simulate', $payment->public_id) }}', '{{ $payment->return_url ? $payment->return_url . '?payment_id=' . $payment->public_id : route('checkout.show', $payment->public_id) }}')"
         x-init="start()">
        <div class="flex items-center gap-2 text-blue-800">
            <span>⚡ <strong>Auto-Pay Demo:</strong> จ่ายเงินอัตโนมัติใน</span>
            <span class="font-mono font-bold text-blue-900 bg-blue-100 px-2 py-0.5 rounded" x-text="secondsLeft + 's'"></span>
        </div>
        <button @click="cancelAutoPay()" x-show="running" class="text-xs text-blue-600 hover:underline">
            หยุดการทำงาน
        </button>
    </div>
    @endif

    <div class="p-6">
        @if($payment->method === 'promptpay')
            <!-- PromptPay Section -->
            <div class="text-center">
                <h2 class="text-lg font-semibold text-gray-700 mb-2">📱 PromptPay QR Code</h2>
                <p class="text-xs text-gray-500 mb-4">สแกน QR ผ่านแอปธนาคาร หรือกดปุ่มจำลองด้านล่าง</p>

                @if($qrPayload)
                    <div class="inline-block p-4 bg-white border-2 border-indigo-100 rounded-xl shadow-md">
                        {!! \App\Services\QrService::generateSvg($qrPayload) !!}
                    </div>
                    <div class="mt-3 flex items-center justify-center gap-2">
                        <span class="text-xs text-gray-400 font-mono">{{ substr($qrPayload, 0, 32) }}...</span>
                        <button type="button"
                                @click="navigator.clipboard.writeText('{{ $qrPayload }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                x-data="{ copied: false }"
                                class="text-xs bg-gray-100 hover:bg-gray-200 px-2 py-1 rounded text-gray-600 transition">
                            <span x-text="copied ? 'Copied!' : 'Copy Payload'"></span>
                        </button>
                    </div>
                @endif

                <div class="mt-6 space-y-3 max-w-md mx-auto">
                    <!-- Simulate Pay Button -->
                    <form action="{{ route('checkout.promptpay', $payment->public_id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="action" value="pay">
                        <button type="submit"
                                class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg shadow transition flex items-center justify-center gap-2">
                            <span>✅</span>
                            <span>จำลองจ่ายแล้ว (COMPLETED)</span>
                        </button>
                    </form>
                    <form action="{{ route('checkout.promptpay', $payment->public_id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="action" value="fail">
                        <button type="submit"
                                class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2.5 px-6 rounded-lg transition">
                            ❌ จำลองยกเลิก / ไม่จ่าย (FAILED)
                        </button>
                    </form>
                </div>
            </div>

        @else
            <!-- Card Section -->
            <h2 class="text-lg font-semibold text-gray-700 mb-1">💳 Credit / Debit Card</h2>
            <p class="text-xs text-gray-500 mb-4">กรอกข้อมูลบัตร หรือคลิกเลือก "เลขบัตรวิเศษ" จากตารางด้านล่าง</p>

            <form action="{{ route('checkout.card', $payment->public_id) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Card Number</label>
                    <input type="text"
                           name="card_number"
                           x-model="cardNumber"
                           placeholder="4242 4242 4242 4242"
                           maxlength="19"
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 font-mono text-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                           @input="formatCardNumber()"
                           required>
                    @error('card_number')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Expiry (MM/YY)</label>
                        <input type="text"
                               name="expiry"
                               x-model="cardExpiry"
                               placeholder="12/28"
                               maxlength="5"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                               required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CVC</label>
                        <input type="text"
                               name="cvc"
                               x-model="cardCvc"
                               placeholder="123"
                               maxlength="4"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                               required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cardholder Name</label>
                    <input type="text"
                           name="cardholder"
                           x-model="cardHolder"
                           placeholder="JOHN DOE"
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                           required>
                </div>

                <button type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg shadow transition">
                    🔒 Pay ฿{{ number_format($payment->amount, 2) }}
                </button>
            </form>
        @endif

        <!-- Test Cards Reference with Quick-Fill buttons -->
        <div class="mt-8 p-4 bg-gray-50 rounded-xl border border-gray-200">
            <details open>
                <summary class="text-sm font-bold text-gray-700 cursor-pointer flex items-center justify-between">
                    <span>🧪 Magic Card Numbers (คลิกเพื่อใส่ข้อมูลอัตโนมัติ)</span>
                    <span class="text-xs text-indigo-600 font-normal">Click to fill</span>
                </summary>
                <div class="mt-3 text-xs space-y-1.5 font-mono">
                    <button type="button" @click="fillCard('4242 4242 4242 4242', '12/28', '123', 'SUCCESS USER')"
                            class="w-full flex justify-between items-center p-2 rounded hover:bg-green-50 text-left transition border border-transparent hover:border-green-200">
                        <span class="font-bold text-green-700">4242 4242 4242 4242</span>
                        <span class="text-green-800 bg-green-100 px-2 py-0.5 rounded font-sans text-xs">✅ สำเร็จ (COMPLETED)</span>
                    </button>
                    <button type="button" @click="fillCard('4000 0000 0000 0002', '12/28', '123', 'DECLINED USER')"
                            class="w-full flex justify-between items-center p-2 rounded hover:bg-red-50 text-left transition border border-transparent hover:border-red-200">
                        <span class="font-bold text-red-700">4000 0000 0000 0002</span>
                        <span class="text-red-800 bg-red-100 px-2 py-0.5 rounded font-sans text-xs">❌ ถูกปฏิเสธ (card_declined)</span>
                    </button>
                    <button type="button" @click="fillCard('4000 0000 0000 9995', '12/28', '123', 'NO FUNDS USER')"
                            class="w-full flex justify-between items-center p-2 rounded hover:bg-red-50 text-left transition border border-transparent hover:border-red-200">
                        <span class="font-bold text-red-700">4000 0000 0000 9995</span>
                        <span class="text-red-800 bg-red-100 px-2 py-0.5 rounded font-sans text-xs">❌ วงเงินไม่พอ (insufficient_funds)</span>
                    </button>
                    <button type="button" @click="fillCard('4000 0000 0000 0069', '01/22', '123', 'EXPIRED USER')"
                            class="w-full flex justify-between items-center p-2 rounded hover:bg-red-50 text-left transition border border-transparent hover:border-red-200">
                        <span class="font-bold text-red-700">4000 0000 0000 0069</span>
                        <span class="text-red-800 bg-red-100 px-2 py-0.5 rounded font-sans text-xs">❌ บัตรหมดอายุ (expired_card)</span>
                    </button>
                    <button type="button" @click="fillCard('4000 0000 0000 0127', '12/28', '999', 'WRONG CVC USER')"
                            class="w-full flex justify-between items-center p-2 rounded hover:bg-red-50 text-left transition border border-transparent hover:border-red-200">
                        <span class="font-bold text-red-700">4000 0000 0000 0127</span>
                        <span class="text-red-800 bg-red-100 px-2 py-0.5 rounded font-sans text-xs">❌ CVC ผิด (incorrect_cvc)</span>
                    </button>
                    <button type="button" @click="fillCard('4000 0000 0000 0119', '12/28', '123', 'ERROR USER')"
                            class="w-full flex justify-between items-center p-2 rounded hover:bg-orange-50 text-left transition border border-transparent hover:border-orange-200">
                        <span class="font-bold text-orange-700">4000 0000 0000 0119</span>
                        <span class="text-orange-800 bg-orange-100 px-2 py-0.5 rounded font-sans text-xs">⚠️ ระบบขัดข้อง (processing_error)</span>
                    </button>
                    <button type="button" @click="fillCard('4000 0000 0000 3220', '12/28', '123', 'OTP USER')"
                            class="w-full flex justify-between items-center p-2 rounded hover:bg-blue-50 text-left transition border border-transparent hover:border-blue-200">
                        <span class="font-bold text-blue-700">4000 0000 0000 3220</span>
                        <span class="text-blue-800 bg-blue-100 px-2 py-0.5 rounded font-sans text-xs">🔐 ต้องยืนยัน OTP (3DS)</span>
                    </button>
                    <button type="button" @click="fillCard('4100 0000 0000 0019', '12/28', '123', 'FRAUD BOT')"
                            class="w-full flex justify-between items-center p-2 rounded hover:bg-purple-50 text-left transition border border-transparent hover:border-purple-200">
                        <span class="font-bold text-purple-700">4100 0000 0000 0019</span>
                        <span class="text-purple-800 bg-purple-100 px-2 py-0.5 rounded font-sans text-xs">🚨 สงสัย Fraud (fraud_suspected)</span>
                    </button>
                    <button type="button" @click="fillCard('4000 0000 0000 0341', '12/28', '123', 'LATE USER')"
                            class="w-full flex justify-between items-center p-2 rounded hover:bg-yellow-50 text-left transition border border-transparent hover:border-yellow-200">
                        <span class="font-bold text-yellow-700">4000 0000 0000 0341</span>
                        <span class="text-yellow-800 bg-yellow-100 px-2 py-0.5 rounded font-sans text-xs">⏳ สำเร็จ + Webhook ช้า 11 นาที</span>
                    </button>
                </div>
            </details>
        </div>
    </div>
</div>

<script>
function checkoutPage() {
    return {
        cardNumber: '',
        cardExpiry: '12/28',
        cardCvc: '123',
        cardHolder: 'JOHN DOE',
        formatCardNumber() {
            this.cardNumber = this.cardNumber.replace(/\D/g,'').replace(/(\d{4})/g,'$1 ').trim().slice(0,19);
        },
        fillCard(number, expiry, cvc, holder) {
            this.cardNumber = number;
            this.cardExpiry = expiry;
            this.cardCvc    = cvc;
            this.cardHolder = holder;
        }
    };
}

function countdownTimer(expiresAt) {
    return {
        display: '--:--',
        start() {
            if (!expiresAt) return;
            const exp = new Date(expiresAt).getTime();
            const tick = () => {
                const diff = Math.max(0, exp - Date.now());
                const m = Math.floor(diff / 60000);
                const s = Math.floor((diff % 60000) / 1000);
                this.display = `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
                if (diff > 0) setTimeout(tick, 1000);
                else this.display = 'EXPIRED';
            };
            tick();
        }
    };
}

function autoPayTimer(seconds, simulateUrl, redirectUrl) {
    return {
        secondsLeft: seconds,
        running: true,
        start() {
            const timer = setInterval(() => {
                if (!this.running) {
                    clearInterval(timer);
                    return;
                }
                this.secondsLeft--;
                if (this.secondsLeft <= 0) {
                    clearInterval(timer);
                    this.executeAutoPay(simulateUrl, redirectUrl);
                }
            }, 1000);
        },
        cancelAutoPay() {
            this.running = false;
        },
        executeAutoPay(url, redirect) {
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ status: 'COMPLETED' })
            }).then(() => {
                window.location.href = redirect;
            });
        }
    };
}
</script>
@endsection
