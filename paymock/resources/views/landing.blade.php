<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayMock — Mock Payment Gateway</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes pulse-slow { 0%,100%{opacity:1} 50%{opacity:.6} }
        .animate-pulse-slow { animation: pulse-slow 2.5s ease-in-out infinite; }
        .gradient-bg { background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%); }
    </style>
</head>
<body class="bg-gray-950 text-white min-h-screen">

<!-- Hero -->
<div class="gradient-bg min-h-[380px] flex flex-col items-center justify-center text-center px-4 py-16 relative overflow-hidden">
    <!-- Decorative blobs -->
    <div class="absolute top-0 left-0 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-purple-600/20 rounded-full blur-3xl translate-x-1/2 translate-y-1/2"></div>

    <div class="relative z-10">
        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur px-4 py-1.5 rounded-full text-sm font-medium mb-6 border border-white/20">
            <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse-slow inline-block"></span>
            <span>Gateway Online · Port 8081</span>
        </div>

        <h1 class="text-5xl font-extrabold tracking-tight mb-3">
            💳 PayMock
        </h1>
        <p class="text-xl text-indigo-200 font-medium mb-2">Mock Payment Gateway</p>
        <p class="text-sm text-indigo-300 max-w-xl mx-auto">
            จำลอง Payment Gateway แบบ Standalone สำหรับทดสอบระบบจองตั๋วคอนเสิร์ต<br>
            รองรับ บัตรเครดิต · PromptPay · Webhook · Chaos Mode
        </p>

        @if($chaosMode !== 'normal')
        <div class="mt-4 inline-flex items-center gap-2 bg-red-600/80 text-white px-4 py-2 rounded-full text-sm font-bold border border-red-400 animate-pulse">
            ⚠️ CHAOS MODE: {{ strtoupper($chaosMode) }}
        </div>
        @endif
    </div>
</div>

<!-- Stats Bar -->
<div class="max-w-5xl mx-auto -mt-8 px-4 relative z-10">
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="bg-gray-800 border border-gray-700 rounded-xl p-4 text-center shadow-lg">
            <div class="text-2xl font-bold text-white">{{ $stats['total'] }}</div>
            <div class="text-xs text-gray-400 mt-1">ทั้งหมด</div>
        </div>
        <div class="bg-green-900/60 border border-green-700/50 rounded-xl p-4 text-center shadow-lg">
            <div class="text-2xl font-bold text-green-400">{{ $stats['completed'] }}</div>
            <div class="text-xs text-green-300 mt-1">COMPLETED</div>
        </div>
        <div class="bg-red-900/60 border border-red-700/50 rounded-xl p-4 text-center shadow-lg">
            <div class="text-2xl font-bold text-red-400">{{ $stats['failed'] }}</div>
            <div class="text-xs text-red-300 mt-1">FAILED</div>
        </div>
        <div class="bg-blue-900/60 border border-blue-700/50 rounded-xl p-4 text-center shadow-lg">
            <div class="text-2xl font-bold text-blue-400">{{ $stats['pending'] }}</div>
            <div class="text-xs text-blue-300 mt-1">PENDING</div>
        </div>
        <div class="bg-yellow-900/60 border border-yellow-700/50 rounded-xl p-4 text-center shadow-lg">
            <div class="text-2xl font-bold text-yellow-400">{{ $stats['expired'] }}</div>
            <div class="text-xs text-yellow-300 mt-1">EXPIRED</div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="max-w-5xl mx-auto px-4 py-10 space-y-8">

    <!-- Quick Links -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('admin.login') }}"
           class="group bg-gray-800 hover:bg-indigo-900/80 border border-gray-700 hover:border-indigo-500 rounded-xl p-6 transition flex flex-col gap-2">
            <div class="text-3xl">🎛️</div>
            <div class="font-bold text-lg">Admin Panel</div>
            <div class="text-sm text-gray-400">จัดการ Payments · Chaos Mode · Webhook Inspector</div>
            <div class="text-xs text-indigo-400 group-hover:text-indigo-300 mt-auto">เปิด Admin →</div>
        </a>

        <a href="{{ url('/v1/payments') }}"
           class="group bg-gray-800 hover:bg-green-900/80 border border-gray-700 hover:border-green-500 rounded-xl p-6 transition flex flex-col gap-2">
            <div class="text-3xl">🔌</div>
            <div class="font-bold text-lg">API Status</div>
            <div class="text-sm text-gray-400">ตรวจสถานะ API · ดูรายการล่าสุด</div>
            <div class="text-xs text-green-400 group-hover:text-green-300 mt-auto font-mono">GET /v1/payments →</div>
        </a>

        <a href="{{ asset('PayMock_API_Reference.docx') }}" download
           class="group bg-gray-800 hover:bg-blue-900/80 border border-gray-700 hover:border-blue-500 rounded-xl p-6 transition flex flex-col gap-2">
            <div class="text-3xl">📄</div>
            <div class="font-bold text-lg">เอกสาร API (.docx)</div>
            <div class="text-sm text-gray-400">คู่มือเชื่อมต่อ API ฉบับ Microsoft Word ครบถ้วน</div>
            <div class="text-xs text-blue-400 group-hover:text-blue-300 mt-auto">Download Word (.docx) →</div>
        </a>

        <a href="{{ asset('paymock.postman_collection.json') }}" download
           class="group bg-gray-800 hover:bg-purple-900/80 border border-gray-700 hover:border-purple-500 rounded-xl p-6 transition flex flex-col gap-2">
            <div class="text-3xl">📥</div>
            <div class="font-bold text-lg">Postman Collection</div>
            <div class="text-sm text-gray-400">18 requests พร้อม environment variables</div>
            <div class="text-xs text-purple-400 group-hover:text-purple-300 mt-auto">Download JSON →</div>
        </a>
    </div>

    <!-- Modal Playground Banner -->
    <div class="bg-gradient-to-r from-indigo-900/90 via-purple-900/80 to-indigo-900/90 border border-indigo-500/50 rounded-2xl p-6 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="text-4xl bg-indigo-800/80 p-3 rounded-xl border border-indigo-500/30">🎪</div>
            <div>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-500/30 text-indigo-300 border border-indigo-400/30 mb-1">
                    ✨ Interactive Playground
                </div>
                <h3 class="text-lg font-bold text-white">ทดสอบจ่ายเงินแบบ Modal Popup (ไม่ต้อง Redirect)</h3>
                <p class="text-sm text-indigo-200 mt-0.5">
                    จำลองหน้าจองตั๋วคอนเสิร์ต เปิด iframe modal ในหน้าเดิม และดักฟังผลลัพธ์ผ่าน postMessage พร้อมตัวอย่างโค้ดก๊อปไปใช้
                </p>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
            <a href="http://localhost:5000" target="_blank"
               class="whitespace-nowrap bg-amber-500 hover:bg-amber-400 text-slate-950 font-black px-4 py-3 rounded-xl shadow-lg transition flex items-center gap-1.5 text-sm">
                <span>🎫 เว็บคอนเสิร์ต (Port 5000)</span>
                <span>↗</span>
            </a>
            <a href="{{ route('demo.modal') }}"
               class="whitespace-nowrap bg-indigo-500 hover:bg-indigo-400 text-white font-bold px-4 py-3 rounded-xl shadow-lg transition flex items-center gap-2 text-sm group">
                <span>ลองเล่น Demo Modal</span>
                <span class="group-hover:translate-x-1 transition-transform">→</span>
            </a>
        </div>
    </div>

    <!-- API Reference -->
    <div class="bg-gray-800 border border-gray-700 rounded-xl p-6">
        <h2 class="text-lg font-bold mb-4 flex items-center gap-2">📡 API Endpoints</h2>
        <div class="space-y-2 font-mono text-sm">
            <div class="flex items-center gap-3 p-3 bg-gray-900/60 rounded-lg">
                <span class="bg-blue-600 text-white px-2 py-0.5 rounded text-xs font-bold min-w-[48px] text-center">GET</span>
                <span class="text-gray-300">/v1/payments</span>
                <span class="ml-auto text-gray-500 text-xs font-sans">ตรวจสถานะ API + รายการล่าสุด</span>
            </div>
            <div class="flex items-center gap-3 p-3 bg-gray-900/60 rounded-lg">
                <span class="bg-green-600 text-white px-2 py-0.5 rounded text-xs font-bold min-w-[48px] text-center">POST</span>
                <span class="text-gray-300">/v1/payments</span>
                <span class="ml-auto text-gray-500 text-xs font-sans">สร้างรายการจ่ายเงิน (Idempotent)</span>
            </div>
            <div class="flex items-center gap-3 p-3 bg-gray-900/60 rounded-lg">
                <span class="bg-blue-600 text-white px-2 py-0.5 rounded text-xs font-bold min-w-[48px] text-center">GET</span>
                <span class="text-gray-300">/v1/payments/{id}</span>
                <span class="ml-auto text-gray-500 text-xs font-sans">ดูสถานะรายการ (Polling)</span>
            </div>
            <div class="flex items-center gap-3 p-3 bg-gray-900/60 rounded-lg">
                <span class="bg-green-600 text-white px-2 py-0.5 rounded text-xs font-bold min-w-[48px] text-center">POST</span>
                <span class="text-gray-300">/v1/payments/{id}/cancel</span>
                <span class="ml-auto text-gray-500 text-xs font-sans">ยกเลิกรายการที่ยัง PENDING</span>
            </div>
            <div class="flex items-center gap-3 p-3 bg-gray-900/60 rounded-lg">
                <span class="bg-yellow-600 text-white px-2 py-0.5 rounded text-xs font-bold min-w-[48px] text-center">GET</span>
                <span class="text-gray-300">/checkout/{id}</span>
                <span class="ml-auto text-gray-500 text-xs font-sans">หน้าจ่ายเงินจำลอง (QR + Card)</span>
            </div>
        </div>
    </div>

    <!-- Auth Info -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-gray-800 border border-gray-700 rounded-xl p-5">
            <h3 class="font-bold text-sm text-gray-300 mb-3">🔑 Authentication</h3>
            <div class="space-y-2 text-xs font-mono">
                <div>
                    <div class="text-gray-500 mb-1">API Key (Bearer)</div>
                    <div class="bg-gray-900 px-3 py-2 rounded-lg text-indigo-300 break-all">{{ config('paymock.api_key') }}</div>
                </div>
                <div>
                    <div class="text-gray-500 mb-1">Webhook HMAC Secret</div>
                    <div class="bg-gray-900 px-3 py-2 rounded-lg text-yellow-300 break-all">{{ config('paymock.webhook_secret') }}</div>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 border border-gray-700 rounded-xl p-5">
            <h3 class="font-bold text-sm text-gray-300 mb-3">💳 Magic Cards (ทดสอบด่วน)</h3>
            <div class="space-y-1.5 text-xs font-mono">
                <div class="flex justify-between items-center bg-gray-900 px-2.5 py-1.5 rounded">
                    <span class="text-green-400">4242 4242 4242 4242</span>
                    <span class="text-gray-400 font-sans">✅ สำเร็จ</span>
                </div>
                <div class="flex justify-between items-center bg-gray-900 px-2.5 py-1.5 rounded">
                    <span class="text-red-400">4000 0000 0000 0002</span>
                    <span class="text-gray-400 font-sans">❌ ถูกปฏิเสธ</span>
                </div>
                <div class="flex justify-between items-center bg-gray-900 px-2.5 py-1.5 rounded">
                    <span class="text-purple-400">4100 0000 0000 0019</span>
                    <span class="text-gray-400 font-sans">🚨 Fraud</span>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="block text-center text-indigo-400 hover:text-indigo-300 text-[11px] pt-1">
                    ดูทั้งหมด 9 ใบ →
                </a>
            </div>
        </div>
    </div>

    <!-- Tech Stack Footer -->
    <div class="text-center text-xs text-gray-600 pb-4">
        <div class="flex flex-wrap justify-center gap-x-4 gap-y-1">
            <span>Laravel 12</span>
            <span>·</span>
            <span>SQLite</span>
            <span>·</span>
            <span>HMAC-SHA256 Webhook</span>
            <span>·</span>
            <span>Idempotency-Key</span>
            <span>·</span>
            <span>Chaos Engineering</span>
            <span>·</span>
            <span>Docker</span>
        </div>
        <div class="mt-1 text-gray-700">PayMock v1.0 · Mock Payment Gateway for Concert Ticketing System</div>
    </div>

</div>
</body>
</html>
