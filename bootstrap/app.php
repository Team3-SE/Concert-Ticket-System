<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            '/dev/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // เมื่อยังไม่ login แล้วพยายามเข้าหน้า protected → redirect กลับ /
        // พร้อม flash session เพื่อให้ JS เปิด modal login อัตโนมัติ
        $exceptions->render(function (AuthenticationException $e, \Illuminate\Http\Request $request) {
            if (! $request->expectsJson()) {
                return redirect('/')
                    ->with('open_modal', 'login')
                    ->withErrors(['auth' => 'กรุณาเข้าสู่ระบบก่อนเข้าใช้งาน']);
            }
        });
    })->create();

