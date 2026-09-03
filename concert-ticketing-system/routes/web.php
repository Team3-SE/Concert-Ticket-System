<?php

use Illuminate\Support\Facades\Route;

// Main home page (using team3-ticket mockup)
Route::get('/', function () {
    return view('home');
});

// ===== DEV TOOLS (Model Tester) =====
Route::get('/dev/models', function () {
    return view('dev.model-tester');
});
Route::post('/dev/models/methods', [\App\Http\Controllers\Dev\ModelTesterController::class, 'getMethods'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/dev/models/execute', [\App\Http\Controllers\Dev\ModelTesterController::class, 'execute'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// ===== PROFILE (แก้ไขข้อมูลส่วนตัว) — mockup, ยังไม่เชื่อมข้อมูลจริง =====

// แสดงหน้าแก้ไขข้อมูลส่วนตัว
Route::get('/profile/edit', function () {
    // TODO: เปลี่ยนเป็นดึงข้อมูล user จริง เช่น auth()->user()
    $user = null;

    return view('profile.edit', compact('user'));
})->name('profile.edit');

// บันทึกการแก้ไขข้อมูลส่วนตัว (ชื่อ / นามสกุล / เบอร์โทรศัพท์)
Route::put('/profile/update', function () {
    // TODO: เพิ่ม validation และ logic บันทึกข้อมูลจริงภายหลัง
    return redirect()->route('profile.edit')->with('status', 'บันทึกข้อมูลส่วนตัวเรียบร้อย (mockup)');
})->name('profile.update');

// บันทึกรหัสผ่านใหม่
Route::put('/profile/password/update', function () {
    // TODO: เพิ่ม validation ตรวจสอบรหัสผ่านเดิม และ hash รหัสผ่านใหม่ภายหลัง
    return redirect()->route('profile.edit')->with('status', 'เปลี่ยนรหัสผ่านเรียบร้อย (mockup)');
})->name('profile.password.update');

// ===== ORDER HISTORY (ประวัติการสั่งซื้อ) — mockup, ยังไม่เชื่อมข้อมูลจริง =====

Route::get('/orders/history', function () {
    // ยังไม่เชื่อมข้อมูลจริง — ตอนนี้ทำแถวตัวอย่างไว้คร่าว ๆ เพื่อดูหน้าตา
    // ทีหลังค่อยดึงข้อมูลจริงจากตาราง orders ของ user ที่ login อยู่ แล้วส่งเข้า view เพื่อ loop แสดงรายการสั่งซื้อจริง
    return view('orders.history');
})->name('orders.history');