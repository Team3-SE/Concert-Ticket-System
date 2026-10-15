<?php

use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PayMock API Routes  (/v1/*)
|--------------------------------------------------------------------------
| Middleware stack: ChaosMiddleware → VerifyApiKey
*/
Route::prefix('v1')
    ->middleware(['chaos', 'verify.api_key'])
    ->group(function () {
        // รายการ payment และสถานะ API
        Route::get('payments', [PaymentController::class, 'index']);

        // สร้าง payment
        Route::post('payments', [PaymentController::class, 'store']);

        // ดูสถานะ
        Route::get('payments/{publicId}', [PaymentController::class, 'show']);

        // ยกเลิก
        Route::post('payments/{publicId}/cancel', [PaymentController::class, 'cancel']);
    });

// ไฟล์ภาพ SVG QR Code (ไม่ต้องใช้ Bearer token เพื่อให้แท็ก <img> หน้าเว็บโหลดได้โดยตรง)
Route::prefix('v1')
    ->group(function () {
        Route::get('payments/{publicId}/qr.svg', [PaymentController::class, 'qrSvg'])->name('api.payment.qr');
    });
