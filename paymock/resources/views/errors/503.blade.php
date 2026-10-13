@extends('layouts.app')
@section('title', '503 Service Unavailable')

@section('content')
<div class="bg-white rounded-xl shadow-lg overflow-hidden border-t-4 border-red-600">
    <div class="px-6 py-12 text-center">
        <div class="text-6xl mb-4">🔴</div>
        <h1 class="text-2xl font-bold text-red-700">503 Service Unavailable</h1>
        <p class="text-gray-700 font-semibold mt-2">ระบบชำระเงินขัดข้องชั่วคราว (Chaos Mode: DOWN)</p>
        <p class="text-gray-500 text-sm mt-1">Payment Gateway is temporarily unavailable or in chaos simulation.</p>

        <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg text-xs text-left font-mono space-y-1 text-red-900 max-w-md mx-auto">
            <div><strong>Status:</strong> HTTP 503 Service Unavailable</div>
            <div><strong>Simulated Error:</strong> gateway_down</div>
            <div><strong>Action:</strong> ระบบหลักต้องจับ Error แล้วปลดล็อกที่นั่งทันที</div>
        </div>

        <div class="mt-8 flex justify-center gap-4">
            <a href="{{ route('admin.dashboard') }}"
               class="bg-gray-800 hover:bg-gray-900 text-white text-sm font-semibold py-2.5 px-6 rounded-lg transition">
                ⚙️ ไปที่ Admin Panel เพื่อปิด Chaos Mode
            </a>
        </div>
    </div>
</div>
@endsection
