<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📱 Mobile Banking Simulator — PromptPay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Prompt', sans-serif; }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex items-center justify-center p-4">

    <!-- Mobile Device Frame -->
    <div class="w-full max-w-sm bg-slate-950 rounded-[36px] border-4 border-slate-700 shadow-2xl overflow-hidden flex flex-col relative">
        
        <!-- Phone Speaker & Notch -->
        <div class="h-6 bg-slate-900 flex items-center justify-center relative">
            <div class="w-16 h-1.5 bg-slate-700 rounded-full"></div>
        </div>

        <!-- Bank App Header -->
        <div class="bg-gradient-to-r from-emerald-600 to-teal-700 p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs bg-white/20 font-bold px-2 py-0.5 rounded-full">K-Mock Bank</span>
                <span class="text-[11px] opacity-80 font-mono">{{ date('H:i') }} น.</span>
            </div>
            <h1 class="text-lg font-bold">📲 จำลองแอปธนาคาร (PromptPay)</h1>
            <p class="text-xs text-teal-100 mt-0.5">ยืนยันการโอนเงินเข้าบัญชีผู้จัดงานคอนเสิร์ต</p>
        </div>

        <!-- Content Area -->
        <div class="p-6 space-y-5 bg-white text-gray-800 flex-1">
            
            @if(session('status') === 'success' || $payment->status === 'COMPLETED')
                <!-- Success State -->
                <div class="text-center py-6 space-y-3">
                    <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-3xl mx-auto shadow-inner">
                        ✓
                    </div>
                    <h2 class="text-xl font-bold text-emerald-700">โอนเงินสำเร็จแล้ว!</h2>
                    <p class="text-xs text-gray-500">
                        ยอดเงิน <strong>฿{{ number_format($payment->amount, 2) }}</strong> ถูกส่งไปยังระบบคอนเสิร์ตเรียบร้อย
                    </p>
                    <p class="text-[11px] text-gray-400 mt-4">
                        * คุณสามารถปิดหน้านี้ได้เลย หน้าจอคอนเสิร์ตจะออกตั๋วให้อัตโนมัติ
                    </p>
                </div>
            @elseif($payment->status === 'EXPIRED')
                <!-- Expired State -->
                <div class="text-center py-8 space-y-3">
                    <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center text-3xl mx-auto">
                        ⏰
                    </div>
                    <h2 class="text-lg font-bold text-amber-800">QR Code หมดอายุแล้ว</h2>
                    <p class="text-xs text-gray-500">รายการนี้เกินเวลาที่กำหนด กรุณากดจองตั๋วใหม่อีกครั้ง</p>
                </div>
            @else
                <!-- Pending Transfer Confirmation Form -->
                <div class="space-y-4">
                    <div class="text-center pb-3 border-b border-gray-100">
                        <span class="text-xs text-gray-400">ยอดเงินที่ต้องชำระ</span>
                        <div class="text-3xl font-black text-emerald-600 font-mono mt-0.5">
                            ฿{{ number_format($payment->amount, 2) }}
                        </div>
                    </div>

                    <!-- Transfer Details -->
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-gray-100">
                            <span class="text-gray-500">ผู้รับเงิน:</span>
                            <span class="font-bold text-gray-800">บริษัท จำหน่ายตั๋วคอนเสิร์ต จำกัด</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-100">
                            <span class="text-gray-500">Transaction ID:</span>
                            <span class="font-mono font-bold text-indigo-700">{{ $payment->public_id }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-100">
                            <span class="text-gray-500">ช่องทาง:</span>
                            <span class="font-bold text-gray-800">PromptPay QR Code</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-500">ค่าธรรมเนียม:</span>
                            <span class="font-bold text-emerald-600">ฟรี (฿0.00)</span>
                        </div>
                    </div>

                    <!-- Action Button Form -->
                    <form action="{{ route('bank.simulate.pay', $payment->public_id) }}" method="POST" class="pt-3">
                        @csrf
                        <button type="submit"
                                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 px-4 rounded-xl shadow-md transition flex items-center justify-center gap-2 text-sm cursor-pointer">
                            <span>🟢 ยืนยันการโอนเงิน (คลิกเพื่อจ่าย)</span>
                        </button>
                    </form>

                    <p class="text-[11px] text-gray-400 text-center">
                        * หน้านี้คือระบบจำลองแอปธนาคารสำหรับ PromptPay
                    </p>
                </div>
            @endif

        </div>

        <!-- Phone Home Bar -->
        <div class="h-6 bg-white flex items-center justify-center pb-2">
            <div class="w-32 h-1 bg-gray-300 rounded-full"></div>
        </div>

    </div>

</body>
</html>
