<?php

/**
 * routes/admin.php
 * หลังบ้านของ Admin: ทุก route ขึ้นต้น /admin ชื่อขึ้นต้น admin. ต้อง login และ role = ADMIN
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 27/10/2569
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

/*
 * โหลดจาก routes/web.php
 * เมนูใน sidebar (partials/backoffice/sidebar) ผูกกับชื่อ route ไว้แล้ว เพิ่ม route ชื่อเหล่านี้แล้วเมนูจะกดได้เอง:
 *   admin.incidents.index (รายการความผิดปกติ), admin.bookings.index (ตรวจสอบการจอง),
 *   admin.audit-logs.index (ประวัติการใช้งาน), admin.organizations.index (จัดการองค์กร)
 */

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:ADMIN'])
    ->group(function () {

        Route::redirect('/', '/admin/users');

        // จัดการผู้ใช้ – หน้าแรกหลัง login
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
    });
