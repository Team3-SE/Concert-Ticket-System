<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

// Main home page
Route::get('/', [HomeController::class, 'index']);

// ===== DEV TOOLS (Model Tester) =====
Route::get('/dev/models', function () {
    return view('dev.model-tester');
});
Route::post('/dev/models/methods', [\App\Http\Controllers\Dev\ModelTesterController::class, 'getMethods'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/dev/models/execute', [\App\Http\Controllers\Dev\ModelTesterController::class, 'execute'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
