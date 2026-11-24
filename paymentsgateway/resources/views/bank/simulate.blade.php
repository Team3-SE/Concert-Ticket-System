<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>K-Mock Bank — ยืนยันการชำระเงินด้วย PromptPay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Prompt', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 text-gray-800 min-h-[100dvh] flex flex-col">

    {{-- แถบหัวเว็บ: ใช้ได้ทุกขนาดหน้าจอ --}}
    <header class="bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <span class="text-xs sm:text-sm bg-white/20 font-bold px-3 py-1 rounded-full whitespace-nowrap">K-Mock Bank</span>
                <span class="hidden sm:inline text-sm text-teal-50 truncate">ระบบจำลองธนาคาร สำหรับทดสอบการชำระเงิน PromptPay</span>
            </div>
            <span class="text-xs sm:text-sm opacity-90 font-mono whitespace-nowrap">{{ date('H:i') }} น.</span>
        </div>
    </header>

    <main class="flex-1 w-full max-w-5xl mx-auto px-4 sm:px-6 py-6 sm:py-10">

        <div class="mb-5 sm:mb-8">
            <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900">ยืนยันการโอนเงิน (PromptPay)</h1>
            <p class="text-sm sm:text-base text-gray-500 mt-1">ตรวจสอบรายละเอียดแล้วกดยืนยันเพื่อโอนเงินเข้าบัญชีผู้จัดงานคอนเสิร์ต</p>
        </div>

        @if(session('status') === 'success' || $payment->status === 'COMPLETED')
            {{-- สำเร็จ --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 sm:p-12 text-center max-w-xl mx-auto space-y-3">
                <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-4xl mx-auto">✓</div>
                <h2 class="text-2xl font-bold text-emerald-700">โอนเงินสำเร็จแล้ว!</h2>
                <p class="text-sm sm:text-base text-gray-500">
                    ยอดเงิน <strong>฿{{ number_format($payment->amount, 2) }}</strong> ถูกส่งไปยังระบบคอนเสิร์ตเรียบร้อย
                </p>
                <p class="text-xs sm:text-sm text-gray-400 pt-2">
                    คุณสามารถปิดหน้านี้ได้เลย หน้าจอคอนเสิร์ตจะออกตั๋วให้อัตโนมัติ
                </p>
            </div>

        @elseif($payment->status === 'EXPIRED')
            {{-- หมดอายุ --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 sm:p-12 text-center max-w-xl mx-auto space-y-3">
                <div class="w-20 h-20 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center text-4xl mx-auto">⏰</div>
                <h2 class="text-2xl font-bold text-amber-800">QR Code หมดอายุแล้ว</h2>
                <p class="text-sm sm:text-base text-gray-500">รายการนี้เกินเวลาที่กำหนด กรุณากดจองตั๋วใหม่อีกครั้ง</p>
            </div>

        @else
            {{-- รอยืนยัน: มือถือ = คอลัมน์เดียว / จอใหญ่ = 2 คอลัมน์ --}}
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-5 lg:gap-8 items-start">

                {{-- รายละเอียดการโอน --}}
                <section class="lg:col-span-3 bg-white rounded-2xl shadow-sm border border-gray-200 p-5 sm:p-8 order-2 lg:order-1">
                    <h2 class="text-base sm:text-lg font-bold text-gray-900 mb-4">รายละเอียดรายการ</h2>
                    <dl class="text-sm sm:text-base divide-y divide-gray-100">
                        <div class="flex flex-col sm:flex-row sm:justify-between gap-0.5 sm:gap-4 py-3">
                            <dt class="text-gray-500">ผู้รับเงิน</dt>
                            <dd class="font-bold text-gray-800 sm:text-right">บริษัท จำหน่ายตั๋วคอนเสิร์ต จำกัด</dd>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:justify-between gap-0.5 sm:gap-4 py-3">
                            <dt class="text-gray-500">Booking Code</dt>
                            <dd class="font-mono font-bold text-indigo-700 sm:text-right break-all">{{ $payment->order_ref }}</dd>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:justify-between gap-0.5 sm:gap-4 py-3">
                            <dt class="text-gray-500">ช่องทาง</dt>
                            <dd class="font-bold text-gray-800 sm:text-right">PromptPay QR Code</dd>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:justify-between gap-0.5 sm:gap-4 py-3">
                            <dt class="text-gray-500">ค่าธรรมเนียม</dt>
                            <dd class="font-bold text-emerald-600 sm:text-right">ฟรี (฿0.00)</dd>
                        </div>
                    </dl>
                </section>

                {{-- ยอดเงิน + ปุ่มยืนยัน --}}
                <section class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 p-5 sm:p-8 order-1 lg:order-2 lg:sticky lg:top-6">
                    <div class="text-center pb-5 border-b border-gray-100">
                        <span class="text-sm text-gray-400">ยอดเงินที่ต้องชำระ</span>
                        <div class="text-4xl sm:text-5xl font-black text-emerald-600 font-mono mt-1 break-all">
                            ฿{{ number_format($payment->amount, 2) }}
                        </div>
                    </div>

                    <form action="{{ route('bank.simulate.pay', $payment->public_id) }}" method="POST" class="pt-5">
                        @csrf
                        <button type="submit"
                                class="w-full bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white font-bold py-4 px-4 rounded-xl shadow-md transition flex items-center justify-center gap-2 text-base cursor-pointer">
                            🟢 ยืนยันการโอนเงิน (คลิกเพื่อจ่าย)
                        </button>
                    </form>

                    <p class="text-xs sm:text-sm text-gray-400 text-center mt-4">
                        * หน้านี้คือระบบจำลองธนาคารสำหรับ PromptPay
                    </p>
                </section>

            </div>
        @endif

    </main>

    <footer class="py-4 text-center text-xs text-gray-400" style="padding-bottom:max(1rem, env(safe-area-inset-bottom))">
        K-Mock Bank · ระบบจำลองสำหรับทดสอบเท่านั้น
    </footer>

</body>
</html>
