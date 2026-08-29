


// ===== DEV TOOLS (Model Tester) =====
if (app()->environment('local')) {
    Route::match(['post', 'options'], '/dev/models/methods', [\App\Http\Controllers\Dev\ModelTesterController::class, 'getMethods'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
    Route::match(['post', 'options'], '/dev/models/execute', [\App\Http\Controllers\Dev\ModelTesterController::class, 'execute'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
}
