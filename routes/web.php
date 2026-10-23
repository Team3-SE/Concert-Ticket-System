<?php

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketController;

$statelessDevMiddleware = [
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    VerifyCsrfToken::class,
];

Route::view('/welcome', 'welcome');
// ===== จัดการรูปภาพคอนเสิร์ต =====
Route::get('/manage-images', [\App\Http\Controllers\ConcertImageController::class, 'index'])->name('manage.images');
Route::put('/manage/concerts/{concertId}', [\App\Http\Controllers\ConcertImageController::class, 'update'])->name('manage.concerts.update');
Route::post('/manage/images/{concertId}/{type}', [\App\Http\Controllers\ConcertImageController::class, 'upload']);
Route::delete('/manage/images/{concertId}/{type}', [\App\Http\Controllers\ConcertImageController::class, 'destroy']);

// Main home page (using team3-ticket mockup)
Route::get('/', [\App\Http\Controllers\HomeController::class, 'index']);

// หน้า "คอนเสิร์ตทั้งหมด" (ปุ่ม Show All)
Route::get('/concerts', [\App\Http\Controllers\ConcertController::class, 'index'])->name('concerts.index');

// ค้นหาคอนเสิร์ตแบบแนะนำขณะพิมพ์ (JSON) – ใช้กับช่องค้นหาหน้า Home / คอนเสิร์ตทั้งหมด
Route::get('/concerts/suggest', [\App\Http\Controllers\ConcertController::class, 'suggest'])->name('concerts.suggest');

// ===== DEV TOOLS (Model Tester) =====
Route::get('/dev/models', function () {
    return view('dev.model-tester');
})->withoutMiddleware($statelessDevMiddleware);

Route::post('/dev/models/methods', [\App\Http\Controllers\Dev\ModelTesterController::class, 'getMethods'])->withoutMiddleware($statelessDevMiddleware);
Route::post('/dev/models/execute', [\App\Http\Controllers\Dev\ModelTesterController::class, 'execute'])->withoutMiddleware($statelessDevMiddleware);

// Route Concert-detail
Route::get('/concert-detail', [\App\Http\Controllers\BookingController::class, 'detail']);

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

    // ===== TICKETS =====

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticketCode}', [TicketController::class, 'show'])->name('tickets.show');

    // ===== ORDER HISTORY =====

    Route::get('/orders/history', [OrderController::class, 'history'])->name('orders.history');

    // ===== BOOKING & PAYMENT =====

    // สร้างการจองจากที่นั่งที่เลือก (ล็อกที่นั่ง) แล้วไปหน้าชำระเงิน
    Route::post('/bookings', [\App\Http\Controllers\BookingController::class, 'store'])->name('bookings.store');

    // สร้างรายการชำระเงิน + ออกหมายเลขคำสั่งซื้อ (แสดงในหน้าต่าง QR)
    Route::post('/payments', [\App\Http\Controllers\PaymentController::class, 'store'])->name('payments.store');

    // สถานะการจอง (หน้าชำระเงินใช้ตรวจตอนหมดเวลา)
    Route::get('/bookings/{bookingId}/status', [\App\Http\Controllers\PaymentController::class, 'status'])
        ->whereNumber('bookingId')
        ->name('bookings.status');

    // ยกเลิกการจองที่ยังไม่ชำระเงิน (ปุ่มย้อนกลับหน้าชำระเงิน) ปลดล็อกที่นั่งทันที
    Route::post('/bookings/{bookingId}/cancel', [\App\Http\Controllers\PaymentController::class, 'cancel'])
        ->whereNumber('bookingId')
        ->name('bookings.cancel');

    // จำลองการชำระเงินสำเร็จ (เฉพาะ APP_ENV=local)
    Route::post('/payments/{paymentId}/mock-complete', [\App\Http\Controllers\PaymentController::class, 'mockComplete'])
        ->whereNumber('paymentId')
        ->name('payments.mock-complete');

});

// ===== AUTH (สมัครสมาชิก / เข้าสู่ระบบ / ออกจากระบบ) =====
Route::post('/auth/register', [\App\Http\Controllers\AuthController::class, 'register'])->name('auth.register');
Route::post('/auth/login',    [\App\Http\Controllers\AuthController::class, 'login'])->name('auth.login');
Route::post('/auth/logout',   [\App\Http\Controllers\AuthController::class, 'logout'])->name('auth.logout');
