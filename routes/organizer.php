<?php

/**
 * routes/organizer.php
 * หลังบ้านของ Organizer: ทุก route ขึ้นต้น /organizer ชื่อขึ้นต้น organizer. ต้อง login และ role = ORGANIZER
 * โหลดจาก routes/web.php
 * เมนูใน sidebar (partials/backoffice/sidebar) ผูกกับชื่อ route ไว้แล้ว เพิ่ม route ชื่อเหล่านี้แล้วเมนูจะกดได้เอง:
 * organizer.concerts.index (จัดการคอนเสิร์ต), organizer.buyers.index (รายการผู้ซื้อตั๋ว),
 * organizer.staff.index (จัดการเจ้าหน้าที่)
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 27/10/2569
 * Updated : 28/11/2569 67160434 จิตฏิยากร โสภา
 */

use App\Http\Controllers\CheckInController;
use App\Http\Controllers\Organizer\ConcertController;
use App\Http\Controllers\Organizer\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('organizer')
    ->name('organizer.')
    ->middleware(['auth', 'role:ORGANIZER'])
    ->group(function () {

        Route::redirect('/', '/organizer/dashboard');

        // แผงควบคุม (Dashboard) – หน้าแรกหลัง login
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // รองรับลิงก์จากหน้าอื่นแบบ /organizer/concert?concert_id={id}
        Route::get('/concert', function (\Illuminate\Http\Request $request) {
            $concertId = (int) $request->query('concert_id');

            if ($concertId > 0) {
                return redirect()->route('organizer.concerts.check', ['concertId' => $concertId]);
            }

            return redirect()->route('organizer.concerts.index');
        })->name('concert.detail');

        // คง route name ให้ Sidebar และส่งต่อไปหน้าจัดการคอนเสิร์ตที่มีอยู่แล้ว
        Route::redirect('/concerts', '/manage-images')->name('concerts.index');

        // ตรวจสอบรายละเอียดคอนเสิร์ต ผังที่นั่ง และรายงานยอดขาย
        Route::get('/concerts/{concertId}/check', [ConcertController::class, 'checkConcert'])->name('concerts.check');

        // ดึงข้อมูลสถานะคอนเสิร์ตและผังที่นั่งแบบ Real-time (JSON)
        Route::get('/concerts/{concertId}/check/realtime', [ConcertController::class, 'getRealtimeConcertStatus'])->name('concerts.check.realtime');

        // เปลี่ยนสถานะการเปิดหรือหยุดจำหน่ายคอนเสิร์ต
        Route::post('/concerts/{concertId}/status', [ConcertController::class, 'updateStatus'])->name('concerts.update-status');

        // ตรวจสอบสิทธิ์เข้างาน (แบบจอคอม) – ใช้ระบบตรวจตั๋วเดียวกับหน้า Staff
        Route::get('/check-in', [CheckInController::class, 'organizer'])->name('check-in');
    });
