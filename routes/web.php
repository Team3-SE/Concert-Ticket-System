<?php

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

$statelessDevMiddleware = [
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    VerifyCsrfToken::class,
];

Route::get('/welcome', function(){
    return view('welcome');
});

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

