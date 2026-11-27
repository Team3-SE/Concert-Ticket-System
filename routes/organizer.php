<?php

/**
 * routes/organizer.php
 * หลังบ้านของ Organizer: ทุก route ขึ้นต้น /organizer ชื่อขึ้นต้น organizer. ต้อง login และ role = ORGANIZER
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 27/10/2569
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

/*
 * โหลดจาก routes/web.php
 * เมนูใน sidebar (partials/backoffice/sidebar) ผูกกับชื่อ route ไว้แล้ว เพิ่ม route ชื่อเหล่านี้แล้วเมนูจะกดได้เอง:
 *   organizer.concerts.index (จัดการคอนเสิร์ต), organizer.buyers.index (รายการผู้ซื้อตั๋ว),
 *   organizer.staff.index (จัดการเจ้าหน้าที่)
 */

use App\Http\Controllers\CheckInController;
use App\Http\Controllers\Organizer\ConcertManagementController;
use App\Http\Controllers\Organizer\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('organizer')
    ->name('organizer.')
    ->middleware(['auth', 'role:ORGANIZER'])
    ->group(function () {

        Route::redirect('/', '/organizer/dashboard');

        // แผงควบคุม (Dashboard) – หน้าแรกหลัง login
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // จัดการคอนเสิร์ต
        Route::get('/concerts', [ConcertManagementController::class, 'index'])->name('concerts.index');
        Route::get('/concerts/suggest', [ConcertManagementController::class, 'suggest'])->name('concerts.suggest');
        Route::get('/concerts/{concertId}/edit', [ConcertManagementController::class, 'edit'])
            ->whereNumber('concertId')
            ->name('concerts.edit');
        Route::delete('/concerts/{concertId}', [ConcertManagementController::class, 'destroy'])
            ->whereNumber('concertId')
            ->name('concerts.destroy');

        // ตรวจสอบสิทธิ์เข้างาน (แบบจอคอม) – ใช้ระบบตรวจตั๋วเดียวกับหน้า Staff
        Route::get('/check-in', [CheckInController::class, 'organizer'])->name('check-in');
    });
