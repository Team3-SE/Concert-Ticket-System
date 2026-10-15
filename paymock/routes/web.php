<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\DemoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Checkout Routes (หน้าจ่ายเงินจำลอง)
|--------------------------------------------------------------------------
*/
Route::prefix('checkout')
    ->middleware('chaos')
    ->group(function () {
        Route::get('{publicId}', [CheckoutController::class, 'show'])->name('checkout.show');
        Route::post('{publicId}/card', [CheckoutController::class, 'processCard'])->name('checkout.card');
        Route::post('{publicId}/promptpay', [CheckoutController::class, 'processPromptPay'])->name('checkout.promptpay');
        Route::post('{publicId}/simulate', [CheckoutController::class, 'simulate'])->name('checkout.simulate');
    });

/*
|--------------------------------------------------------------------------
| Admin Panel Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Login
        Route::get('login', [AdminController::class, 'loginForm'])->name('login');
        Route::post('login', [AdminController::class, 'login'])->name('login.post');
        Route::post('logout', [AdminController::class, 'logout'])->name('logout');

        // Protected admin routes
        Route::middleware('admin.auth')->group(function () {
            Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
            Route::post('chaos', [AdminController::class, 'setChaos'])->name('chaos');
            Route::post('webhook-mode', [AdminController::class, 'setWebhookMode'])->name('webhook.mode');
            Route::post('scenarios/run', [AdminController::class, 'runScenario'])->name('scenarios.run');
            Route::post('payments/{publicId}/transition', [AdminController::class, 'transition'])->name('payments.transition');
            Route::post('payments/{publicId}/resend-webhook', [AdminController::class, 'resendWebhook'])->name('payments.resend');
            Route::post('clear-all', [AdminController::class, 'clearAll'])->name('clear.all');
            Route::post('webhook-test', [AdminController::class, 'webhookTest'])->name('webhook.test');
        });
    });

// Health check (used by Docker)
Route::get('/up', fn () => response()->json(['status' => 'ok']));

// Landing Page
Route::get('/', [LandingController::class, 'index'])->name('landing');

// Demo Playground (Concert Ticket Modal Integration)
Route::get('/demo/modal', [DemoController::class, 'modal'])->name('demo.modal');
Route::post('/demo/create-payment', [DemoController::class, 'createDemoPayment'])
    ->name('demo.create.payment')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
Route::post('/api/demo-webhook-sink', fn () => response()->json(['status' => 'received']))
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

// Bank App Simulator (หน้าจำลองแอปธนาคารสำหรับ PromptPay QR Code)
Route::get('/bank-simulate/{publicId}', [\App\Http\Controllers\BankSimulatorController::class, 'show'])->name('bank.simulate.show');
Route::post('/bank-simulate/{publicId}/pay', [\App\Http\Controllers\BankSimulatorController::class, 'pay'])
    ->name('bank.simulate.pay')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);



