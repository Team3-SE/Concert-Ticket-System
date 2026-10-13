<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\LandingController;
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
