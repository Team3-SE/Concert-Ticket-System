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

// Main home page (using team3-ticket mockup)
Route::get('/', [\App\Http\Controllers\HomeController::class, 'index']);

// ===== DEV TOOLS (Model Tester) =====
Route::get('/dev/models', function () {
    return view('dev.model-tester');
})->withoutMiddleware($statelessDevMiddleware);
Route::post('/dev/models/methods', [\App\Http\Controllers\Dev\ModelTesterController::class, 'getMethods'])->withoutMiddleware($statelessDevMiddleware);
Route::post('/dev/models/execute', [\App\Http\Controllers\Dev\ModelTesterController::class, 'execute'])->withoutMiddleware($statelessDevMiddleware);

// ===== ADMIN: Audit Logs =====
Route::prefix('admin')->group(function () {
    Route::get('/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index']);
    Route::get('/audit-logs/export', [\App\Http\Controllers\AuditLogController::class, 'export']);
    Route::get('/audit-logs/{logId}', [\App\Http\Controllers\AuditLogController::class, 'show']);
});
