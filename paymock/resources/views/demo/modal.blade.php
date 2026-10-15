@extends('layouts.app')
@section('title', 'Demo: Payment Modal Integration')

@section('content')
<div class="max-w-4xl mx-auto space-y-8" x-data="demoModalApp()">

    <!-- Header & Breadcrumb -->
    <div class="flex items-center justify-between border-b border-gray-200 pb-4">
        <div>
            <div class="text-xs uppercase tracking-wider font-bold text-indigo-600 mb-1">Developer Playground</div>
            <h1 class="text-2xl font-black text-gray-900 flex items-center gap-2">
                🎪 ระบบจำลองการจองตั๋วคอนเสิร์ต (Modal Integration)
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                ตัวอย่างการฝัง PayMock ลงในหน้าเว็บคอนเสิร์ตด้วย <strong>Modal + iframe + postMessage</strong> โดยไม่ต้อง Redirect ข้ามหน้า
            </p>
        </div>
        <a href="{{ route('landing') }}" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg transition font-medium">
            ← กลับหน้าหลัก
        </a>
    </div>

    <!-- Live Demo Card -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Left: Ticket Summary -->
        <div class="md:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-700 to-indigo-700 p-6 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="bg-white/20 text-white text-[11px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">Live Concert 2026</span>
                        <h2 class="text-2xl font-black mt-2">MAROON 5 WORLD TOUR BANGKOK</h2>
                        <p class="text-purple-200 text-sm mt-1">📍 Impact Arena, Muang Thong Thani</p>
                    </div>
                    <div class="text-3xl">🎸</div>
                </div>
            </div>

            <div class="p-6 space-y-4">
                <div class="flex justify-between items-center py-2 border-b border-gray-100 text-sm">
                    <span class="text-gray-500">โซนที่นั่ง:</span>
                    <span class="font-bold text-gray-800">Zone A (Standing VIP)</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100 text-sm">
                    <span class="text-gray-500">จำนวนที่นั่ง:</span>
                    <span class="font-bold text-gray-800">1 ใบ (Seat #A-42)</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100 text-sm">
                    <span class="text-gray-500">ช่องทางชำระเงินที่ต้องการ:</span>
                    <select x-model="method" class="border border-gray-300 rounded-lg px-2 py-1 text-sm bg-white font-medium">
                        <option value="promptpay">PromptPay QR</option>
                        <option value="card">บัตรเครดิต / เดบิต</option>
                    </select>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100 text-sm">
                    <span class="text-gray-500">Scenario จำลอง:</span>
                    <select x-model="scenario" class="border border-gray-300 rounded-lg px-2 py-1 text-sm bg-white font-mono text-xs">
                        <option value="">normal (ชำระตามปกติ)</option>
                        <option value="webhook_lost">webhook_lost (จำลอง Webhook สูญหาย)</option>
                        <option value="webhook_delay:10">webhook_delay:10 (จำลอง Webhook ช้า 10 วินาที)</option>
                        <option value="webhook_dup:3">webhook_dup:3 (จำลอง Webhook ส่งซ้ำ 3 ครั้ง)</option>
                        <option value="webhook_bad_sig">webhook_bad_sig (จำลองลายเซ็น Signature ไม่ถูกต้อง)</option>
                        <option value="webhook_replay">webhook_replay (จำลอง Replay Attack Timestamp หมดอายุ)</option>
                    </select>
                </div>
                <div class="flex justify-between items-center pt-2 text-base font-black">
                    <span class="text-gray-900">ยอดชำระสุทธิ:</span>
                    <span class="text-2xl text-indigo-600 font-mono">฿1,500.00</span>
                </div>

                <!-- CTA Button -->
                <button @click="openPaymentModal()"
                        :disabled="loading"
                        class="w-full mt-4 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold py-3.5 px-6 rounded-xl shadow-md hover:shadow-lg transition flex items-center justify-center gap-2 text-base cursor-pointer">
                    <span x-show="!loading">💳 ชำระเงินผ่าน Modal Popup (คลิกเพื่อทดสอบ)</span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        กำลังสร้างรายการชำระเงิน...
                    </span>
                </button>
            </div>
        </div>

        <!-- Right: Status / Event Log -->
        <div class="bg-gray-900 text-white rounded-2xl p-5 flex flex-col justify-between font-mono text-xs border border-gray-800">
            <div>
                <div class="flex items-center justify-between border-b border-gray-800 pb-2 mb-3">
                    <span class="text-gray-400 font-bold uppercase tracking-wider text-[10px]">📡 Parent Event Logs</span>
                    <span class="inline-flex items-center gap-1.5 text-[10px] text-green-400">
                        <span class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></span> Listening
                    </span>
                </div>
                <div class="space-y-2 text-gray-300 max-h-64 overflow-y-auto" id="logContainer">
                    <template x-for="(log, idx) in logs" :key="idx">
                        <div class="p-2 rounded bg-gray-800/80 border border-gray-700">
                            <div class="text-[10px] text-gray-400" x-text="log.time"></div>
                            <div :class="log.color" class="font-bold mt-0.5" x-text="log.msg"></div>
                            <div x-show="log.payload" class="text-[10px] text-gray-400 mt-1 break-all" x-text="JSON.stringify(log.payload)"></div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Ticket Status Box -->
            <div class="mt-4 pt-3 border-t border-gray-800">
                <div class="text-gray-400 text-[11px] mb-1">สถานะตั๋วในเว็บคอนเสิร์ต:</div>
                <div class="text-sm font-bold flex items-center gap-2" :class="ticketStatusColor">
                    <span x-text="ticketStatusIcon"></span>
                    <span x-text="ticketStatusText"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Code Guide Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                💻 โค้ดตัวอย่างสำหรับนำไปใส่ในเว็บคอนเสิร์ต (Copy & Paste ได้เลย)
            </h2>
            <span class="text-xs bg-indigo-50 text-indigo-700 font-bold px-2.5 py-1 rounded-md">HTML + JS Native</span>
        </div>

        <p class="text-sm text-gray-600">
            เมื่อผู้ใช้กดจองตั๋ว ให้ฝั่ง Frontend ของคุณเปิด Modal แล้วใส่ <code>checkout_url</code> ใน <code>&lt;iframe&gt;</code> จากนั้นดักฟัง Event <code>message</code>:
        </p>

        <div class="bg-gray-900 rounded-xl p-4 text-emerald-400 font-mono text-xs overflow-x-auto relative">
<pre><code>&lt;!-- 1. Modal Container บนเว็บคอนเสิร์ต --&gt;
&lt;div id="paymentModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9999; justify-content:center; align-items:center;"&gt;
    &lt;div style="background:white; border-radius:16px; overflow:hidden; width:95%; max-width:540px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.5);"&gt;
        &lt;div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:#f3f4f6; border-bottom:1px solid #e5e7eb;"&gt;
            &lt;span style="font-weight:bold; font-size:14px; color:#374151;"&gt;ชำระเงินค่าตั๋วคอนเสิร์ต&lt;/span&gt;
            &lt;button onclick="closePaymentModal()" style="border:none; background:none; font-size:18px; cursor:pointer; color:#6b7280;"&gt;✕&lt;/button&gt;
        &lt;/div&gt;
        &lt;!-- iframe แสดงหน้า PayMock --&gt;
        &lt;iframe id="paymentFrame" src="" style="width:100%; height:620px; border:none;"&gt;&lt;/iframe&gt;
    &lt;/div&gt;
&lt;/div&gt;

&lt;!-- 2. JavaScript จัดการเปิด Modal และดักฟัง Event postMessage --&gt;
&lt;script&gt;
function openModalWithUrl(checkoutUrl) {
    document.getElementById('paymentFrame').src = checkoutUrl;
    document.getElementById('paymentModal').style.display = 'flex';
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
    document.getElementById('paymentFrame').src = '';
}

// ดักฟังข้อความจาก PayMock เมื่อจ่ายเงินเสร็จหรือกดปิด
window.addEventListener('message', function(event) {
    if (!event.data || !event.data.type) return;

    // เมื่อผู้ใช้กดปุ่มปิดใน iframe
    if (event.data.type === 'paymock:close_modal') {
        closePaymentModal();
    }

    // เมื่อสถานะการชำระเงินเปลี่ยน (COMPLETED / FAILED)
    if (event.data.type === 'paymock:payment_result') {
        console.log('Payment result:', event.data);
        if (event.data.status === 'COMPLETED') {
            closePaymentModal();
            alert('🎉 ชำระเงินสำเร็จ! ออกตั๋วคอนเสิร์ตเรียบร้อย');
            window.location.reload(); // หรือรีเฟรชหน้าเพื่ออัปเดตตั๋ว
        }
    }
});
&lt;/script&gt;</code></pre>
        </div>
    </div>

    <!-- MODAL POPUP (HTML Component) -->
    <div x-show="modalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
         style="display: none;">
        
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden w-full max-w-lg border border-gray-200 flex flex-col"
             @click.away="closeModal()">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-4 py-3 bg-gray-100 border-b border-gray-200 text-sm">
                <div class="flex items-center gap-2">
                    <span class="text-indigo-600 font-bold">🔒 Secure Payment Popup</span>
                    <span class="text-xs text-gray-500 font-mono">(iframe container)</span>
                </div>
                <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-700 text-lg font-bold px-2 py-0.5 rounded cursor-pointer">
                    ✕
                </button>
            </div>

            <!-- iframe wrapper -->
            <div class="w-full bg-gray-50 flex items-center justify-center relative" style="height: 640px;">
                <iframe :src="currentCheckoutUrl" class="w-full h-full border-0" allow="payment"></iframe>
            </div>
        </div>
    </div>

</div>

<script>
function demoModalApp() {
    return {
        method: 'promptpay',
        scenario: '',
        loading: false,
        modalOpen: false,
        currentCheckoutUrl: '',
        ticketStatusText: 'ยังไม่ได้เริ่มชำระเงิน',
        ticketStatusColor: 'text-gray-400',
        ticketStatusIcon: '⚪',
        logs: [
            { time: new Date().toLocaleTimeString(), msg: 'พร้อมทดสอบ คลิกปุ่มชำระเงินด้านซ้าย', color: 'text-gray-400' }
        ],

        init() {
            // ฟัง postMessage จาก PayMock ภายใน iframe
            window.addEventListener('message', (event) => {
                if (!event.data || !event.data.type) return;

                if (event.data.type === 'paymock:close_modal') {
                    this.addLog('ได้รับคำสั่งปิด Modal (paymock:close_modal)', 'text-yellow-400', event.data);
                    this.closeModal();
                }

                if (event.data.type === 'paymock:payment_result') {
                    this.addLog('ได้รับผลลัพธ์การชำระเงิน (paymock:payment_result)', 'text-emerald-400', event.data);
                    if (event.data.status === 'COMPLETED') {
                        this.ticketStatusText = 'ออกตั๋วสำเร็จแล้ว (PAID) 🎉';
                        this.ticketStatusColor = 'text-green-400';
                        this.ticketStatusIcon = '✅';
                        
                        setTimeout(() => {
                            this.closeModal();
                            alert('🎉 จ่ายเงินสำเร็จ! Modal ปิดตัวอัตโนมัติ และสถานะตั๋วเปลี่ยนเป็น COMPLETED แล้ว');
                        }, 1200);
                    } else if (event.data.status === 'FAILED') {
                        this.ticketStatusText = 'การชำระเงินล้มเหลว (FAILED)';
                        this.ticketStatusColor = 'text-red-400';
                        this.ticketStatusIcon = '❌';
                    }
                }
            });
        },

        addLog(msg, color = 'text-white', payload = null) {
            this.logs.unshift({
                time: new Date().toLocaleTimeString(),
                msg,
                color,
                payload
            });
        },

        async openPaymentModal() {
            this.loading = true;
            this.ticketStatusText = 'กำลังรอชำระเงิน (PENDING)...';
            this.ticketStatusColor = 'text-yellow-400';
            this.ticketStatusIcon = '⏳';
            this.addLog('กำลังส่งคำขอสร้าง Payment...', 'text-blue-400');

            try {
                const res = await fetch('{{ route("demo.create.payment") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        amount: 1500.00,
                        method: this.method,
                        scenario: this.scenario
                    })
                });
                const data = await res.json();
                if (data.ok) {
                    this.addLog(`สร้าง Payment สำเร็จ! ID: ${data.payment_id}`, 'text-green-400', data);
                    this.currentCheckoutUrl = data.checkout_url;
                    this.modalOpen = true;
                } else {
                    this.addLog('สร้างรายการไม่สำเร็จ', 'text-red-400', data);
                }
            } catch (err) {
                this.addLog('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'text-red-400', err.message);
            } finally {
                this.loading = false;
            }
        },

        closeModal() {
            this.modalOpen = false;
            this.currentCheckoutUrl = '';
            this.addLog('ปิดหน้าต่าง Modal เรียบร้อย', 'text-gray-400');
        }
    };
}
</script>
@endsection
