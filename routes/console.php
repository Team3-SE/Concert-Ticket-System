<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ปล่อยที่นั่งของการจองที่ครบ 10 นาทีแล้วไม่ชำระ ทุกนาที (ต้องมี `php artisan schedule:work` รันอยู่)
// ถ้าไม่ได้รัน ระบบยังปล่อยให้เมื่อมีคนเปิดหน้าเลือกที่นั่ง/กดจอง/เปิดหน้าชำระเงิน
Schedule::call(fn () => \App\Models\Booking::releaseExpired())->everyMinute()->name('release-expired-bookings');

// ตรวจรายการชำระเงินที่ค้างรอยืนยันกับ PayMock ทุกนาที (กรณี webhook หาย) — ไม่ตั้งค่า PAYMOCK_URL = ข้ามเอง
Schedule::command('payments:reconcile')->everyMinute()->withoutOverlapping()->name('reconcile-payments');
