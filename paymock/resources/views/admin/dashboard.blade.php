<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayMock Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-gray-100 min-h-screen" x-data="adminDashboard()">
<nav class="bg-gray-900 text-white px-6 py-3 flex justify-between items-center shadow-md">
    <div class="flex items-center gap-3">
        <span class="text-2xl">🎛️</span>
        <div>
            <div class="font-bold text-lg leading-tight">PayMock Admin Panel</div>
            <div class="text-xs text-gray-400">Mock Payment Gateway for Concert System</div>
        </div>
    </div>
    <div class="flex items-center gap-4">
        <a href="http://localhost:5000" target="_blank" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs px-3 py-1.5 rounded-lg transition shadow flex items-center gap-1.5">
            🎫 เว็บคอนเสิร์ต (Port 5000) ↗
        </a>
        <a href="{{ route('demo.modal') }}" class="bg-purple-600 hover:bg-purple-700 text-xs text-white font-semibold px-3 py-1.5 rounded-lg transition shadow flex items-center gap-1.5">
            🎪 Demo Modal
        </a>
        <a href="{{ asset('PayMock_API_Reference.docx') }}" download class="bg-blue-600 hover:bg-blue-700 text-xs text-white font-semibold px-3 py-1.5 rounded-lg transition shadow">
            📄 เอกสาร Word (.docx)
        </a>
        <a href="{{ asset('paymock.postman_collection.json') }}" download class="bg-indigo-600 hover:bg-indigo-700 text-xs text-white font-semibold px-3 py-1.5 rounded-lg transition shadow">
            📥 Postman Collection
        </a>
        <form action="{{ route('admin.clear.all') }}" method="POST" onsubmit="return confirm('ล้างข้อมูลทั้งหมดจริงๆ?')">
            @csrf
            <button type="submit" class="bg-rose-700 hover:bg-rose-800 text-xs text-white font-semibold px-3 py-1.5 rounded-lg transition shadow">
                🗑️ Clear All
            </button>
        </form>
        <span class="text-gray-400 text-sm font-mono">{{ now()->format('d M Y H:i:s') }}</span>
        <form action="{{ route('admin.logout') }}" method="POST" class="inline">
            @csrf
            <button class="text-red-400 hover:text-red-300 text-sm font-medium">Logout</button>
        </form>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- ═══ STATS BAR ═══ --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="bg-white rounded-xl border border-gray-100 shadow p-4 text-center">
            <div class="text-2xl font-extrabold text-gray-800">{{ $stats['total'] }}</div>
            <div class="text-xs text-gray-500 mt-1">ทั้งหมด</div>
        </div>
        <div class="bg-green-50 rounded-xl border border-green-200 shadow p-4 text-center">
            <div class="text-2xl font-extrabold text-green-700">{{ $stats['completed'] }}</div>
            <div class="text-xs text-green-600 mt-1">COMPLETED</div>
        </div>
        <div class="bg-red-50 rounded-xl border border-red-200 shadow p-4 text-center">
            <div class="text-2xl font-extrabold text-red-700">{{ $stats['failed'] }}</div>
            <div class="text-xs text-red-600 mt-1">FAILED</div>
        </div>
        <div class="bg-blue-50 rounded-xl border border-blue-200 shadow p-4 text-center">
            <div class="text-2xl font-extrabold text-blue-700">{{ $stats['pending'] }}</div>
            <div class="text-xs text-blue-600 mt-1">PENDING</div>
        </div>
        <div class="bg-yellow-50 rounded-xl border border-yellow-200 shadow p-4 text-center">
            <div class="text-2xl font-extrabold text-yellow-700">{{ $stats['expired'] }}</div>
            <div class="text-xs text-yellow-600 mt-1">EXPIRED</div>
        </div>
    </div>

    <!-- ═══ CHAOS WARNING BANNER ═══ -->
    @if($chaosMode !== 'normal')
        <div class="p-4 bg-red-600 text-white rounded-xl shadow-lg flex items-center justify-between animate-pulse">
            <div class="flex items-center gap-3">
                <span class="text-2xl">⚠️</span>
                <div>
                    <div class="font-bold text-sm">CHAOS MODE กำลังเปิดทำงาน: <span class="font-mono bg-red-800 px-2 py-0.5 rounded text-white">{{ strtoupper($chaosMode) }}</span></div>
                    <div class="text-xs opacity-90 mt-0.5">
                        ชั้นใหญ่สุด: ทุกคำขอเข้า Checkout และ API (/payments/*) จะถูกจำลองตามโหมดนี้ก่อนยิง webhook
                    </div>
                </div>
            </div>
            <form action="{{ route('admin.chaos') }}" method="POST">
                @csrf
                <input type="hidden" name="chaos_mode" value="normal">
                <button type="submit" class="bg-white text-red-700 hover:bg-gray-100 font-bold px-4 py-2 rounded-lg text-xs shadow transition">
                    🔄 ปิด Chaos (กลับสู่ Normal)
                </button>
            </form>
        </div>
    @endif

    @if(session('success'))
        <div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-xl shadow-sm flex items-center justify-between">
            <span>✅ {{ session('success') }}</span>
            <button @click="$el.parentElement.remove()" class="text-green-600 hover:text-green-900 font-bold">&times;</button>
        </div>
    @endif
    @if(session('info'))
        <div class="p-4 bg-blue-100 border border-blue-300 text-blue-800 rounded-xl shadow-sm flex items-center justify-between">
            <span>ℹ️ {{ session('info') }}</span>
            <button @click="$el.parentElement.remove()" class="text-blue-600 hover:text-blue-900 font-bold">&times;</button>
        </div>
    @endif

    @if($webhookMode !== 'normal')
        <div class="p-4 bg-purple-700 text-white rounded-xl shadow-lg flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-2xl">📡</span>
                <div>
                    <div class="font-bold text-sm">WEBHOOK MODE (ชั้นรอง): <span class="font-mono bg-purple-900 px-2 py-0.5 rounded text-white">{{ strtoupper($webhookMode) }}</span></div>
                    <div class="text-xs opacity-90 mt-0.5">
                        ทุกรายการจะถูกจำลอง webhook ตามโหมดนี้ (lost / delay / dup / bad_sig)
                    </div>
                </div>
            </div>
            <form action="{{ route('admin.webhook.mode') }}" method="POST">
                @csrf
                <input type="hidden" name="webhook_mode" value="normal">
                <button type="submit" class="bg-white text-purple-800 hover:bg-gray-100 font-bold px-4 py-2 rounded-lg text-xs shadow transition">
                    🔄 ปิด Webhook Mode
                </button>
            </form>
        </div>
    @endif

    <!-- ═══ GRID: CHAOS + WEBHOOK GLOBAL ═══ -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- 1. Chaos Mode (ชั้นใหญ่สุด) -->
        <div class="bg-white rounded-xl shadow p-6 border border-gray-100">
            <h2 class="text-lg font-bold text-gray-800 mb-2 flex items-center gap-2">
                <span>⚡</span> ชั้น 1 — Chaos Mode (ทั้งระบบ)
            </h2>
            <p class="text-xs text-gray-500 mb-4">มีผลกับคำขอเข้า API / Checkout ก่อนเสมอ ระบบคอนเสิร์ตไม่ต้องส่ง header อะไร</p>
            
            <form action="{{ route('admin.chaos') }}" method="POST" class="space-y-3">
                @csrf
                <label for="chaos_mode_select" class="block text-xs text-gray-600 mb-1">เลือกโหมด Chaos</label>
                <select id="chaos_mode_select" name="chaos_mode"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none">
                    <option value="normal" {{ $chaosMode === 'normal' ? 'selected' : '' }}>✅ Normal (ทำงานปกติ)</option>
                    <option value="down" {{ $chaosMode === 'down' ? 'selected' : '' }}>🔴 Gateway มีปัญหา (503)</option>
                    <option value="slow" {{ str_starts_with($chaosMode, 'slow:') ? 'selected' : '' }}>🐢 Slow (หน่วงก่อนประมวลผล)</option>
                    <option value="rate_limit" {{ $chaosMode === 'rate_limit' ? 'selected' : '' }}>🚦 Rate Limit (429 Too Many Requests)</option>
                </select>
                <label for="delay_seconds_input" class="block text-xs text-gray-600">
                    เวลา delay (วินาที, ใช้กับ Slow; 0-3600)
                </label>
                <input id="delay_seconds_input" type="number" name="delay_seconds" min="0" max="3600"
                       value="{{ (int) (explode(':', $chaosMode, 2)[1] ?? 5) }}"
                       class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none">
                <button type="submit"
                        class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 rounded-lg text-sm transition shadow">
                    Apply Chaos Mode
                </button>
            </form>
            <div class="mt-4 pt-3 border-t text-xs text-gray-500">
                สถานะปัจจุบัน: <span class="font-mono font-bold text-red-700">{{ $chaosMode }}</span>
            </div>
        </div>

        <!-- 2. Webhook Mode (ชั้นรอง, global) -->
        <div class="bg-white rounded-xl shadow p-6 border border-gray-100">
            <h2 class="text-lg font-bold text-gray-800 mb-2 flex items-center gap-2">
                <span>📡</span> ชั้น 2 — Webhook Mode (ทั้งระบบ)
            </h2>
            <p class="text-xs text-gray-500 mb-4">มีผลกับทุกรายการตอนยิง webhook หลังจ่ายสำเร็จ / ล้มเหลว ไม่มีการ override รายการต่อรายการ</p>

            <form action="{{ route('admin.webhook.mode') }}" method="POST" class="space-y-3">
                @csrf
                <label for="webhook_mode_select" class="block text-xs text-gray-600 mb-1">เลือกโหมด Webhook</label>
                <select id="webhook_mode_select" name="webhook_mode"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    <option value="normal" {{ $webhookMode === 'normal' ? 'selected' : '' }}>✅ Normal (ส่ง webhook ตามจริง)</option>
                    <option value="webhook_lost" {{ $webhookMode === 'webhook_lost' ? 'selected' : '' }}>🟡 Lost (ไม่ส่งเลย — ทดสอบ reconcile)</option>
                    <option value="webhook_dup:3" {{ $webhookMode === 'webhook_dup:3' ? 'selected' : '' }}>🟣 Duplicate ×3</option>
                    <option value="webhook_bad_sig" {{ $webhookMode === 'webhook_bad_sig' ? 'selected' : '' }}>🔴 Bad Signature</option>
                </select>
                <button type="submit"
                        class="w-full bg-purple-700 hover:bg-purple-800 text-white font-bold py-2 rounded-lg text-sm transition shadow">
                    Apply Webhook Mode
                </button>
            </form>
            <div class="mt-4 pt-3 border-t text-xs text-gray-500">
                สถานะปัจจุบัน: <span class="font-mono font-bold text-purple-700">{{ $webhookMode }}</span>
            </div>
        </div>
    </div>

    <!-- 3. One-Click Payment Launcher -->
    <div class="bg-white rounded-xl shadow p-6 border border-gray-100 flex flex-col justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-800 mb-1 flex items-center gap-2">
                    <span>🚀</span> One-Click Payment Launcher
                </h2>
                <p class="text-xs text-gray-500 mb-4">สร้างรายการและเปิดหน้าชำระเงินทันที โดย Webhook Mode ใช้ค่าที่ตั้งไว้ด้านบนกับทุกรายการ</p>

                <!-- ── Section A: กลุ่มวิธีการชำระเงิน ── -->
                <div class="mb-5">
                    <div class="flex items-center gap-2 mb-2 pb-1 border-b border-gray-100">
                        <span class="text-xs font-bold text-indigo-700 uppercase tracking-wider">💳 กลุ่มที่ 1: Payment Methods</span>
                        <span class="text-[10px] text-gray-400 font-normal">(PromptPay หรือบัตรเครดิต — เลขบัตรทดสอบเลือกในหน้าชำระเงิน)</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2.5 max-w-xl">
                        <!-- PromptPay Standard -->
                        <form action="{{ route('admin.scenarios.run') }}" method="POST">
                            @csrf
                            <input type="hidden" name="method" value="promptpay">
                            <button type="submit" class="w-full text-left p-2.5 rounded-lg border border-gray-200 hover:border-green-500 hover:bg-green-50 transition shadow-xs">
                                <div class="text-xs font-bold text-green-700 flex items-center justify-between">
                                    <span>📱 PromptPay QR</span>
                                    <span class="text-[10px] bg-green-100 text-green-800 px-1 rounded">Standard</span>
                                </div>
                                <div class="text-[11px] text-gray-500 mt-0.5">สแกน QR หรือกดจำลองจ่าย</div>
                            </button>
                        </form>

                        <!-- Credit / Debit Card -->
                        <form action="{{ route('admin.scenarios.run') }}" method="POST">
                            @csrf
                            <input type="hidden" name="method" value="card">
                            <button type="submit" class="w-full text-left p-2.5 rounded-lg border border-gray-200 hover:border-indigo-500 hover:bg-indigo-50 transition shadow-xs">
                                <div class="text-xs font-bold text-indigo-700 flex items-center justify-between">
                                    <span>💳 บัตรเครดิต / เดบิต</span>
                                    <span class="text-[10px] bg-indigo-100 text-indigo-800 px-1 rounded">Checkout</span>
                                </div>
                                <div class="text-[11px] text-gray-500 mt-0.5">กรอกบัตรหรือเลือก Magic Card ในหน้าชำระ</div>
                            </button>
                        </form>
                    </div>
                </div>

            </div>

            <!-- Credentials Reference -->
            <div class="mt-4 pt-3 border-t flex flex-wrap gap-4 text-xs font-mono text-gray-500">
                <div>API Key: <code class="bg-gray-100 px-1.5 py-0.5 rounded text-indigo-700">{{ config('paymock.api_key') }}</code></div>
                <div>Secret: <code class="bg-gray-100 px-1.5 py-0.5 rounded text-indigo-700">{{ config('paymock.webhook_secret') }}</code></div>
            </div>
        </div>

    <!-- ═══ LIVE IN-BROWSER API TESTER ═══ -->
    <div class="bg-white rounded-xl shadow p-6 border border-gray-100">
        <div class="flex justify-between items-center mb-2">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <span>🧪</span> Live Gateway Tester (ยิงทดสอบ API ผ่านเบราว์เซอร์เดี๋ยวนี้)
            </h2>
            <div class="text-xs text-gray-500">
                คลิกยิงเพื่อดูว่าเมื่อเปิด Chaos Mode แล้ว Gateway ตอบสนอง HTTP Code อะไรทันที
            </div>
        </div>

        <div class="flex flex-wrap gap-3 my-4">
            <button type="button"
                    @click="testApi('{{ url('/payments') }}', 'POST')"
                    :disabled="testing"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 py-2 rounded-lg text-xs transition shadow flex items-center gap-2 disabled:opacity-50">
                <span x-show="!testing">⚡ ยิงสร้าง Payment (POST /payments)</span>
                <span x-show="testing">⏳ กำลังส่งคำขอ...</span>
            </button>
            <button type="button"
                    @click="testApi('{{ url('/payments') }}', 'GET')"
                    :disabled="testing"
                    class="bg-gray-800 hover:bg-gray-900 text-white font-bold px-4 py-2 rounded-lg text-xs transition shadow flex items-center gap-2 disabled:opacity-50">
                <span>🔍 ตรวจสอบบริการ (GET /payments)</span>
            </button>
        </div>

        <!-- Live Response Output -->
        <div x-show="testResult" x-cloak class="mt-4 p-4 rounded-xl border" :class="testResult && testResult.status < 300 ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'">
            <div class="flex justify-between items-center mb-2">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-xs" :class="testResult && testResult.status < 300 ? 'text-green-800' : 'text-red-800'">
                        HTTP Status: <strong class="font-mono text-sm" x-text="testResult ? testResult.status + ' ' + testResult.statusText : ''"></strong>
                    </span>
                    <span class="text-[11px] bg-white px-2 py-0.5 rounded shadow-sm border font-mono" x-text="testResult ? testResult.duration + ' ms' : ''"></span>
                </div>
                <button @click="testResult = null" class="text-xs text-gray-400 hover:text-gray-600">&times; ปิด</button>
            </div>
            <pre class="bg-gray-900 text-green-400 p-3 rounded-lg text-xs font-mono overflow-x-auto max-h-48"
                 x-text="testResult ? testResult.body : ''"></pre>
        </div>
    </div>

    <!-- ═══ PAYMENTS TABLE ═══ -->
    <div class="bg-white rounded-xl shadow p-6 border border-gray-100">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <span>💳</span> Payments รายการชำระเงิน
            </h2>
            <span class="text-xs text-gray-500">รวม {{ $payments->total() }} รายการ</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b text-xs">
                        <th class="pb-2 pr-4">Provider Txn (txn_…)</th>
                        <th class="pb-2 pr-4">รายละเอียด</th>
                        <th class="pb-2 pr-4">ยอดเงิน</th>
                        <th class="pb-2 pr-4">ช่องทาง</th>
                        <th class="pb-2 pr-4">สถานะ</th>
                        <th class="pb-2 pr-4">Webhook</th>
                        <th class="pb-2 pr-4">เวลาหมดอายุ</th>
                        <th class="pb-2">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-mono text-xs">
                @forelse($payments as $p)
                    <tr class="hover:bg-gray-50" x-data="{ open: false }">
                        <td class="py-2.5 pr-4 text-gray-600 font-semibold">
                            <a href="{{ route('checkout.show', $p->public_id) }}" target="_blank" class="text-indigo-600 hover:underline">
                                {{ $p->public_id }} ↗
                            </a>
                        </td>
                        <td class="py-2.5 pr-4 font-sans">
                            <button type="button"
                                    @click="selectedPayment = {{ \Illuminate\Support\Js::from([
                                        'id' => $p->public_id,
                                        'amount' => (float) $p->amount,
                                        'currency' => $p->currency,
                                        'method' => $p->method,
                                        'status' => $p->status,
                                        'failure_code' => $p->failure_code,
                                        'idempotency_key' => $p->idempotency_key,
                                        'created_at' => $p->created_at?->toIso8601String(),
                                        'expires_at' => $p->expires_at?->toIso8601String(),
                                        'paid_at' => $p->paid_at?->toIso8601String(),
                                        'callback_url' => $p->callback_url,
                                        'return_url' => $p->return_url,
                                        'metadata' => $p->responseMetadata(),
                                    ]) }}"
                                    class="text-indigo-600 hover:text-indigo-800 font-semibold text-xs underline underline-offset-2">
                                🔍 ดูรายละเอียด
                            </button>
                        </td>
                        <td class="py-2.5 pr-4 font-bold font-sans text-sm text-gray-800">฿{{ number_format($p->amount, 2) }}</td>
                        <td class="py-2.5 pr-4 font-sans">
                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold {{ $p->method === 'promptpay' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                {{ $p->method }}
                            </span>
                        </td>
                        <td class="py-2.5 pr-4 font-sans">
                            <span class="px-2 py-0.5 rounded text-xs font-bold {{ match($p->status) {
                                'COMPLETED' => 'bg-green-100 text-green-700',
                                'FAILED'    => 'bg-red-100 text-red-700',
                                'EXPIRED'   => 'bg-yellow-100 text-yellow-700',
                                default     => 'bg-gray-100 text-gray-600',
                            } }}">
                                {{ $p->status }}
                                @if($p->failure_code)
                                    <span class="font-normal text-[11px]">({{ $p->failure_code }})</span>
                                @endif
                            </span>
                        </td>
                        <td class="py-2.5 pr-4 text-gray-500 font-sans text-xs">{{ $webhookMode !== 'normal' ? $webhookMode.' (Admin global)' : 'normal' }}</td>
                        <td class="py-2.5 pr-4 font-sans text-xs text-gray-500">{{ $p->expires_at?->format('H:i:s') ?? '–' }}</td>
                        <td class="py-2.5 font-sans">
                            <div class="flex gap-1 flex-wrap items-center">
                                <div x-show="open" x-cloak class="flex gap-1">
                                    <form action="{{ route('admin.payments.transition', $p->public_id) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="COMPLETED">
                                        <button class="bg-green-600 hover:bg-green-700 text-white text-xs px-2 py-1 rounded shadow">✅ จ่ายสำเร็จ</button>
                                    </form>
                                    <form action="{{ route('admin.payments.transition', $p->public_id) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="FAILED">
                                        <input type="hidden" name="failure_code" value="admin_forced">
                                        <button class="bg-red-600 hover:bg-red-700 text-white text-xs px-2 py-1 rounded shadow">❌ ยกเลิก</button>
                                    </form>
                                    <form action="{{ route('admin.payments.resend', $p->public_id) }}" method="POST" class="inline">
                                        @csrf
                                        <button class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-2 py-1 rounded shadow">🔁 ส่ง Webhook ซ้ำ</button>
                                    </form>
                                </div>
                                <button @click="open = !open"
                                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs px-2 py-1 rounded border">
                                    <span x-text="open ? 'ซ่อน' : 'จัดการ'"></span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-8 text-center text-gray-400 font-sans">ยังไม่มีรายการชำระเงิน</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $payments->links() }}
        </div>
    </div>

    <!-- ═══ MODAL: PAYMENT DETAILS ═══ -->
    <div x-show="selectedPayment" x-cloak
         class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50"
         @click.self="selectedPayment = null"
         @keydown.escape.window="selectedPayment = null">
        <dialog open aria-modal="true" aria-labelledby="payment-detail-title"
            class="m-0 relative bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6 border border-gray-200 max-h-[90vh] overflow-y-auto"
            @click.stop>
            <div class="flex justify-between items-center mb-5">
                <div>
                    <h3 id="payment-detail-title" class="font-bold text-lg text-gray-900">รายละเอียดการชำระเงิน</h3>
                    <p class="text-xs text-gray-500 font-mono mt-1" x-text="selectedPayment?.id"></p>
                </div>
                <button type="button" @click="selectedPayment = null" aria-label="ปิดรายละเอียด"
                        class="text-gray-500 hover:text-gray-900 text-2xl leading-none">&times;</button>
            </div>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div><dt class="text-xs text-gray-500">Transaction ID</dt><dd class="mt-1 font-mono break-all" x-text="selectedPayment?.id || '—'"></dd></div>
                <div><dt class="text-xs text-gray-500">ยอดชำระ</dt><dd class="mt-1 font-semibold" x-text="selectedPayment ? `${selectedPayment.currency} ${Number(selectedPayment.amount).toFixed(2)}` : '—'"></dd></div>
                <div><dt class="text-xs text-gray-500">ช่องทาง / สถานะ</dt><dd class="mt-1" x-text="selectedPayment ? `${selectedPayment.method} / ${selectedPayment.status}` : '—'"></dd></div>
                <div><dt class="text-xs text-gray-500">Failure Code</dt><dd class="mt-1 font-mono" x-text="selectedPayment?.failure_code || '—'"></dd></div>
                <div><dt class="text-xs text-gray-500">Idempotency Key</dt><dd class="mt-1 font-mono break-all" x-text="selectedPayment?.idempotency_key || '—'"></dd></div>
                <div><dt class="text-xs text-gray-500">สร้างเมื่อ</dt><dd class="mt-1 font-mono text-xs break-all" x-text="selectedPayment?.created_at || '—'"></dd></div>
                <div><dt class="text-xs text-gray-500">หมดอายุ</dt><dd class="mt-1 font-mono text-xs break-all" x-text="selectedPayment?.expires_at || '—'"></dd></div>
                <div><dt class="text-xs text-gray-500">ชำระเมื่อ</dt><dd class="mt-1 font-mono text-xs break-all" x-text="selectedPayment?.paid_at || '—'"></dd></div>
                <div><dt class="text-xs text-gray-500">Callback URL</dt><dd class="mt-1 font-mono text-xs break-all" x-text="selectedPayment?.callback_url || '—'"></dd></div>
                <div class="sm:col-span-2"><dt class="text-xs text-gray-500">Return URL</dt><dd class="mt-1 font-mono text-xs break-all" x-text="selectedPayment?.return_url || '—'"></dd></div>
            </dl>
            <div class="mt-5">
                <h4 class="text-xs font-semibold text-gray-600 mb-2">Metadata</h4>
                <pre class="bg-gray-900 text-green-300 p-4 rounded-lg text-xs font-mono overflow-x-auto max-h-56"
                     x-text="selectedPayment ? JSON.stringify(selectedPayment.metadata, null, 2) : ''"></pre>
            </div>
            <div class="mt-5 pt-4 border-t border-gray-200 text-right">
                <button type="button" @click="selectedPayment = null"
                        class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-xs font-semibold">ปิด</button>
            </div>
        </dialog>
    </div>

    <!-- ═══ WEBHOOK DELIVERIES ═══ -->
    <div class="bg-white rounded-xl shadow p-6 border border-gray-100">
        <h2 class="text-lg font-bold text-gray-800 mb-2 flex items-center gap-2">
            <span>📡</span> Webhook Deliveries การส่ง Webhook (50 รายการล่าสุด)
        </h2>
        <p class="text-xs text-gray-500 mb-4">แสดงประวัติการส่ง Webhook พร้อม Event ID และการตอบกลับของระบบคอนเสิร์ต</p>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="pb-2 pr-4">Event ID</th>
                        <th class="pb-2 pr-4">Event Name</th>
                        <th class="pb-2 pr-4">Payment ID</th>
                        <th class="pb-2 pr-4 text-center">จำนวนครั้ง (Attempts)</th>
                        <th class="pb-2 pr-4">HTTP Status</th>
                        <th class="pb-2 pr-4">เวลาที่สำเร็จ</th>
                        <th class="pb-2">Payload</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-mono">
                @forelse($deliveries as $d)
                    <tr class="{{ $d->delivered_at ? 'bg-green-50/50' : ($d->last_error ? 'bg-red-50/50' : '') }}">
                        <td class="py-2 pr-4 text-gray-700 font-bold">{{ $d->event_id }}</td>
                        <td class="py-2 pr-4 font-sans font-semibold text-gray-900">{{ $d->event }}</td>
                        <td class="py-2 pr-4 text-gray-500">{{ $d->payment->public_id ?? '–' }}</td>
                        <td class="py-2 pr-4 text-center">{{ $d->attempts }}</td>
                        <td class="py-2 pr-4">
                            @if($d->last_status_code)
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $d->last_status_code < 300 ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800' }}">
                                    {{ $d->last_status_code }}
                                </span>
                            @else
                                <span class="text-yellow-600 font-sans">กำลังรอส่ง...</span>
                            @endif
                        </td>
                        <td class="py-2 pr-4 font-sans text-gray-500">{{ $d->delivered_at?->format('H:i:s') ?? '–' }}</td>
                        <td class="py-2 font-sans">
                            <button type="button"
                                    @click="selectedDelivery = {{ json_encode($d->payload) }}"
                                    class="text-indigo-600 hover:text-indigo-900 font-medium text-xs">
                                🔍 ดู JSON
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-gray-400 font-sans">ยังไม่มีประวัติการส่ง Webhook</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ═══ MODAL: JSON PAYLOAD INSPECTOR ═══ -->
    <div x-show="selectedDelivery" x-cloak
         class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50"
         @click.self="selectedDelivery = null">
        <div class="bg-gray-900 text-white rounded-xl shadow-2xl max-w-lg w-full p-6 border border-gray-700">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-md text-gray-200">📡 Webhook Payload Inspection</h3>
                <button @click="selectedDelivery = null" class="text-gray-400 hover:text-white text-xl font-bold">&times;</button>
            </div>
            <pre class="bg-black/80 p-4 rounded-lg text-xs font-mono text-green-400 overflow-x-auto max-h-80"
                 x-text="JSON.stringify(selectedDelivery, null, 2)"></pre>
            <div class="mt-4 pt-3 border-t border-gray-800 text-right">
                <button @click="selectedDelivery = null"
                        class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-1.5 rounded-lg text-xs font-semibold">
                    ปิด
                </button>
            </div>
        </div>
    </div>

    <!-- ═══ WEBHOOK TESTER ═══ -->
    <div class="bg-white rounded-xl shadow p-6 border border-gray-100" x-data="webhookTester()">
        <div class="flex justify-between items-center mb-2">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <span>🔬</span> Webhook Signature Tester
            </h2>
            <div class="text-xs text-gray-500">ยิง Test Webhook ไปที่ endpoint ของคุณพร้อม HMAC-SHA256 signature จริง</div>
        </div>
        <p class="text-xs text-gray-400 mb-4">
            ใช้ทดสอบว่า webhook endpoint ของระบบคอนเสิร์ต <strong>ตรวจลายเซ็นถูกต้อง</strong> ก่อนเชื่อมต่อจริง
        </p>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="md:col-span-2">
                <label for="admin_wh_url" class="block text-xs font-semibold text-gray-600 mb-1">Target Webhook URL</label>
                <input id="admin_wh_url" type="url" x-model="whUrl" placeholder="http://concert_backend:5000/api/webhook"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
            </div>
            <div>
                <label for="admin_wh_event" class="block text-xs font-semibold text-gray-600 mb-1">Event Type</label>
                <select id="admin_wh_event" x-model="whEvent" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="payment.completed">payment.completed</option>
                    <option value="payment.failed">payment.failed</option>
                    <option value="payment.expired">payment.expired</option>
                </select>
            </div>
            <div>
                <label for="admin_wh_scenario" class="block text-xs font-semibold text-gray-600 mb-1">Signature Scenario</label>
                <select id="admin_wh_scenario" x-model="whScenario" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono text-xs">
                    <option value="normal">normal (ลายเซ็นถูกต้อง)</option>
                    <option value="webhook_bad_sig">webhook_bad_sig (ลายเซ็นปลอม)</option>
                </select>
            </div>
        </div>

        <button type="button" @click="sendTest()"
                :disabled="whTesting || !whUrl"
                class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold px-5 py-2 rounded-lg text-sm transition shadow flex items-center gap-2">
            <span x-show="!whTesting">🚀 ยิง Test Webhook</span>
            <span x-show="whTesting">⏳ กำลังส่ง...</span>
        </button>

        <!-- Result -->
        <div x-show="whResult" x-cloak class="mt-4 p-4 rounded-xl border"
             :class="whResult && whResult.ok ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'">
            <div class="flex items-center gap-3 mb-3">
                <span class="text-lg" x-text="whResult && whResult.ok ? '✅' : '❌'"></span>
                <div>
                    <div class="font-bold text-sm" :class="whResult && whResult.ok ? 'text-green-800' : 'text-red-800'"
                         x-text="whResult ? (whResult.ok ? 'Webhook Received! HTTP ' + whResult.status : 'Failed: ' + (whResult.error || 'HTTP ' + whResult.status)) : ''">
                    </div>
                    <div class="text-xs text-gray-500">ถ้า endpoint ตรวจ signature ถูก → ต้องตอบ 2xx</div>
                </div>
            </div>

            <!-- Signature info -->
            <div x-show="whResult && whResult.signature" class="mb-3 p-3 bg-gray-900 rounded-lg">
                <div class="text-xs text-gray-400 mb-1">X-Signature ที่ส่งไป:</div>
                <div class="font-mono text-xs text-yellow-300 break-all" x-text="whResult ? whResult.signature : ''"></div>
            </div>

            <!-- Raw body sent -->
            <details class="text-xs">
                <summary class="cursor-pointer text-gray-500 hover:text-gray-700 mb-1">ดู Payload ที่ส่งไป / Response ที่ได้</summary>
                <div class="mt-2 space-y-2">
                    <div>
                        <div class="text-xs text-gray-500 mb-1 font-semibold">Request Body:</div>
                        <pre class="bg-gray-900 text-green-400 p-3 rounded-lg text-xs font-mono overflow-x-auto max-h-32"
                             x-text="whResult ? JSON.stringify(JSON.parse(whResult.raw_body || '{}'), null, 2) : ''"></pre>
                    </div>
                    <div x-show="whResult && whResult.body">
                        <div class="text-xs text-gray-500 mb-1 font-semibold">Response จาก endpoint:</div>
                        <pre class="bg-gray-900 text-blue-400 p-3 rounded-lg text-xs font-mono overflow-x-auto max-h-24"
                             x-text="whResult ? whResult.body : ''"></pre>
                    </div>
                </div>
            </details>
        </div>
    </div>

</div>

<script>
function adminDashboard() {
    return {
        selectedDelivery: null,
        selectedPayment: null,
        testing: false,
        testResult: null,
        async testApi(url, method) {
            this.testing = true;
            this.testResult = null;
            const start = performance.now();
            try {
                const options = {
                    method: method,
                    headers: {
                        'Authorization': 'Bearer {{ config('paymock.api_key') }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'Idempotency-Key': 'DEMO_' + Date.now()
                    }
                };
                if (method === 'POST') {
                    options.body = JSON.stringify({
                        amount: 1500.00,
                        method: 'card',
                        callback_url: 'http://concert_backend:5000/api/webhook'
                    });
                }
                const response = await fetch(url, options);
                const duration = Math.round(performance.now() - start);
                let bodyText = '';
                const contentType = response.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    const json = await response.json();
                    bodyText = JSON.stringify(json, null, 2);
                } else {
                    bodyText = await response.text();
                }
                this.testResult = {
                    status: response.status,
                    statusText: response.statusText,
                    duration: duration,
                    body: bodyText
                };
            } catch (err) {
                const duration = Math.round(performance.now() - start);
                this.testResult = {
                    status: 0,
                    statusText: 'Network / Connection Error',
                    duration: duration,
                    body: err.message
                };
            } finally {
                this.testing = false;
            }
        }
    };
}

function webhookTester() {
    return {
        whUrl: 'http://concert_backend:5000/api/webhook',
        whEvent: 'payment.completed',
        whScenario: 'normal',
        whTesting: false,
        whResult: null,
        async sendTest() {
            this.whTesting = true;
            this.whResult = null;
            try {
                const response = await fetch('{{ route('admin.webhook.test') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ url: this.whUrl, event: this.whEvent, scenario: this.whScenario })
                });
                this.whResult = await response.json();
            } catch (err) {
                this.whResult = { ok: false, error: err.message };
            } finally {
                this.whTesting = false;
            }
        }
    };
}
</script>
</body>
</html>
