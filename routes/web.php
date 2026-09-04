<?php

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;

$statelessDevMiddleware = [
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    VerifyCsrfToken::class,
];

Route::view('/welcome', 'welcome');

// Main home page (using team3-ticket mockup)
Route::get('/', [\App\Http\Controllers\HomeController::class, 'index']);

// ===== DEV TOOLS (Model Tester) =====
Route::get('/dev/models', function () {
    return view('dev.model-tester');
})->withoutMiddleware($statelessDevMiddleware);

Route::post('/dev/models/methods', [\App\Http\Controllers\Dev\ModelTesterController::class, 'getMethods'])->withoutMiddleware($statelessDevMiddleware);
Route::post('/dev/models/execute', [\App\Http\Controllers\Dev\ModelTesterController::class, 'execute'])->withoutMiddleware($statelessDevMiddleware);


// Route Booking
Route::get('/booking', [\App\Http\Controllers\BookingController::class, 'index']);

// Route Payment
Route::get('/payment', [\App\Http\Controllers\PaymentController::class, 'index']);

// ===== AUTHENTICATED ROUTES (ต้อง login ก่อน) =====
Route::middleware('auth')->group(function () {

    // ===== PROFILE =====

    // แสดงหน้าแก้ไขข้อมูลส่วนตัว
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');

    // บันทึกการแก้ไขชื่อ / นามสกุล / เบอร์โทร
    Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');

    // เปลี่ยนรหัสผ่าน
    Route::put('/profile/password/update', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // ===== ORDER HISTORY =====

    Route::get('/orders/history', [OrderController::class, 'history'])->name('orders.history');

});

// ===== AUTH (สมัครสมาชิก / เข้าสู่ระบบ / ออกจากระบบ) =====
Route::post('/auth/register', [\App\Http\Controllers\AuthController::class, 'register'])->name('auth.register');
Route::post('/auth/login',    [\App\Http\Controllers\AuthController::class, 'login'])->name('auth.login');
Route::post('/auth/logout',   [\App\Http\Controllers\AuthController::class, 'logout'])->name('auth.logout');



