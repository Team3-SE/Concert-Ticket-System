<?php

/**
 * routes/staff.php
 * หน้าของ Staff (เจ้าหน้าที่หน้างาน): ใช้ได้แค่ตรวจสอบสิทธิ์เข้างาน ต้อง login และ role = STAFF
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 27/10/2569
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

/*
 * หน้าออกแบบให้ใช้บนมือถือเป็นหลัก แต่เปิดได้ทุกขนาดจอ
 * โหลดจาก routes/web.php
 */

use App\Http\Controllers\CheckInController;
use Illuminate\Support\Facades\Route;

Route::prefix('staff')
    ->name('staff.')
    ->middleware(['auth', 'role:STAFF'])
    ->group(function () {

        Route::redirect('/', '/staff/check-in');

        // ตรวจสอบสิทธิ์เข้างาน (สแกน QR / กรอกรหัสตั๋ว) – หน้าแรกและหน้าเดียวของ Staff
        Route::get('/check-in', [CheckInController::class, 'staff'])->name('check-in');
    });
