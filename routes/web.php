<?php

/**
 * routes/web.php
 * route ฝั่ง User (ลูกค้า + คนที่ยังไม่ login), เข้าสู่ระบบ/ออกจากระบบ และเครื่องมือ dev
 * หลังบ้านแยกไฟล์ (require ท้ายไฟล์นี้): routes/organizer.php, routes/admin.php, routes/staff.php
 * role ของแต่ละ route ตรวจด้วย middleware role (app/Http/Middleware/EnsureRole.php)
 *   - role:GUEST,USER = ลูกค้าและคนที่ยังไม่ login เท่านั้น (Organizer/Admin/Staff ถูกพากลับหน้าแรกของ role ตัวเอง)
 * Updated : 27/10/2569 67160292 ธีรเมธ พลสาย
 */

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Controllers\User\OrderController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\TicketController;

$statelessDevMiddleware = [
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    VerifyCsrfToken::class,
];

Route::view('/welcome', 'welcome');
// ===== จัดการรูปภาพคอนเสิร์ต (หน้าเดิม ยังไม่ได้จำกัด role: view อยู่ที่ organizer/concerts/manage-images) =====
Route::get('/manage-images', [\App\Http\Controllers\Organizer\ConcertImageController::class, 'index'])->name('manage.images');
Route::put('/manage/concerts/{concertId}', [\App\Http\Controllers\Organizer\ConcertImageController::class, 'update'])->name('manage.concerts.update');
Route::post('/manage/images/{concertId}/{type}', [\App\Http\Controllers\Organizer\ConcertImageController::class, 'upload']);
Route::delete('/manage/images/{concertId}/{type}', [\App\Http\Controllers\Organizer\ConcertImageController::class, 'destroy']);

// ===== DEV TOOLS (Model Tester) =====
Route::get('/dev/models', function () {
    return view('dev.model-tester');
})->withoutMiddleware($statelessDevMiddleware);

Route::post('/dev/models/methods', [\App\Http\Controllers\Dev\ModelTesterController::class, 'getMethods'])->withoutMiddleware($statelessDevMiddleware);
Route::post('/dev/models/execute', [\App\Http\Controllers\Dev\ModelTesterController::class, 'execute'])->withoutMiddleware($statelessDevMiddleware);

// ===== ฝั่ง User: เปิดได้ทั้งลูกค้าและคนที่ยังไม่ login =====
Route::middleware('role:GUEST,USER')->group(function () {

    // Main home page (using team3-ticket mockup)
    Route::get('/', [\App\Http\Controllers\User\HomeController::class, 'index']);

    // หน้า "คอนเสิร์ตทั้งหมด" (ปุ่ม Show All)
    Route::get('/concerts', [\App\Http\Controllers\User\ConcertController::class, 'index'])->name('concerts.index');

    // ค้นหาคอนเสิร์ตแบบแนะนำขณะพิมพ์ (JSON) – ใช้กับช่องค้นหาหน้า Home / คอนเสิร์ตทั้งหมด
    Route::get('/concerts/suggest', [\App\Http\Controllers\User\ConcertController::class, 'suggest'])->name('concerts.suggest');

    // Route Concert-detail
    Route::get('/concert-detail', [\App\Http\Controllers\User\BookingController::class, 'detail']);

    // Route Booking
    Route::get('/booking', [\App\Http\Controllers\User\BookingController::class, 'index']);

    // Route Payment
    Route::get('/payment', [\App\Http\Controllers\User\PaymentController::class, 'index']);

    // ===== สมัครสมาชิก + ยืนยันอีเมล =====
    Route::post('/auth/register', [\App\Http\Controllers\AuthController::class, 'register'])->name('auth.register');

    Route::get('/email/verify', [\App\Http\Controllers\User\EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{token}', [\App\Http\Controllers\User\EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/resend', [\App\Http\Controllers\User\EmailVerificationController::class, 'resend'])
        ->middleware('throttle:3,1')->name('verification.resend');
});

// ===== ฝั่ง User ที่ต้อง login ก่อน (เฉพาะลูกค้า) =====
Route::middleware(['auth', 'role:USER'])->group(function () {

    // ===== PROFILE =====

    // แสดงหน้าแก้ไขข้อมูลส่วนตัว
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');

    // บันทึกการแก้ไขชื่อ / นามสกุล / เบอร์โทร
    Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');

    // เปลี่ยนรหัสผ่าน
    Route::put('/profile/password/update', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // ===== TICKETS =====

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticketCode}', [TicketController::class, 'show'])->name('tickets.show');

    // ===== ORDER HISTORY =====

    Route::get('/orders/history', [OrderController::class, 'history'])->name('orders.history');

    // ===== BOOKING & PAYMENT =====

    // สร้างการจองจากที่นั่งที่เลือก (ล็อกที่นั่ง) แล้วไปหน้าชำระเงิน
    Route::post('/bookings', [\App\Http\Controllers\User\BookingController::class, 'store'])->name('bookings.store');

    // สร้างรายการชำระเงิน + ออกหมายเลขคำสั่งซื้อ (แสดงในหน้าต่าง QR)
    Route::post('/payments', [\App\Http\Controllers\User\PaymentController::class, 'store'])->name('payments.store');

    // สถานะการจอง (หน้าชำระเงินใช้ตรวจตอนหมดเวลา)
    Route::get('/bookings/{bookingId}/status', [\App\Http\Controllers\User\PaymentController::class, 'status'])
        ->whereNumber('bookingId')
        ->name('bookings.status');

    // ยกเลิกการจองที่ยังไม่ชำระเงิน (ปุ่มย้อนกลับหน้าชำระเงิน) ปลดล็อกที่นั่งทันที
    Route::post('/bookings/{bookingId}/cancel', [\App\Http\Controllers\User\PaymentController::class, 'cancel'])
        ->whereNumber('bookingId')
        ->name('bookings.cancel');

    // จำลองการชำระเงินสำเร็จ (เฉพาะ APP_ENV=local)
    Route::post('/payments/{paymentId}/mock-complete', [\App\Http\Controllers\User\PaymentController::class, 'mockComplete'])
        ->whereNumber('paymentId')
        ->name('payments.mock-complete');

});

// ===== เข้าสู่ระบบ / ออกจากระบบ (ใช้ร่วมทุก role: หน้าต่างเข้าสู่ระบบเดียวกัน แล้วพาไปหน้าแรกของ role) =====
Route::post('/auth/login',  [\App\Http\Controllers\AuthController::class, 'login'])->name('auth.login');
Route::post('/auth/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('auth.logout');

// ===== หลังบ้าน =====
require __DIR__.'/organizer.php';
require __DIR__.'/admin.php';
require __DIR__.'/staff.php';
