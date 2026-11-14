<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

        // ไม่มี route ชื่อ login (ฟอร์มเข้าสู่ระบบเป็น modal ในหน้า Home) → ระบุปลายทางเองเพื่อไม่ให้ middleware auth หา route('login') แล้ว error
        // การเปิด modal ทำใน withExceptions ด้านล่าง
        $middleware->redirectGuestsTo('/');

        // role:ORGANIZER, role:GUEST,USER ... จำกัด route ตาม role (app/Http/Middleware/EnsureRole.php)
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // เมื่อยังไม่ login แล้วพยายามเข้าหน้า protected → redirect กลับ /
        // พร้อม flash session เพื่อให้ JS เปิด modal login อัตโนมัติ
        $exceptions->render(function (AuthenticationException $e, \Illuminate\Http\Request $request) {
            if (! $request->expectsJson()) {
                // จำหน้าที่จะเข้า (เช่น /organizer/check-in) ไว้ในช่อง redirect ของหน้าต่าง login → login เสร็จกลับมาหน้านั้น
                return redirect('/')
                    ->with('open_modal', 'login')
                    ->withInput($request->isMethod('get') ? ['redirect' => $request->getRequestUri()] : [])
                    ->withErrors(['auth' => 'กรุณาเข้าสู่ระบบก่อนเข้าใช้งาน']);
            }
        });

        // ฟอร์มที่เปิดค้างไว้นานจน session หมดอายุ (CSRF token ไม่ตรง) เดิมจะขึ้นหน้า 419 PAGE EXPIRED
        // → พากลับไปหน้าเดิม (ได้ token ใหม่) พร้อมข้อความให้ลองอีกครั้ง
        $exceptions->render(function (HttpException $e, Request $request) {
            if (! $e->getPrevious() instanceof TokenMismatchException || $request->expectsJson()) {
                return null;
            }

            $message = 'หน้านี้เปิดค้างไว้นานจนหมดอายุ กรุณาลองใหม่อีกครั้ง';

            // ออกจากระบบ: session หมดอายุแล้ว เท่ากับออกจากระบบไปแล้ว → กลับหน้า Home
            if ($request->routeIs('auth.logout')) {
                return redirect('/');
            }

            // ฟอร์มเข้าสู่ระบบ/สมัครสมาชิกอยู่ในหน้าต่างของหน้า Home → เปิดหน้าต่างเดิมพร้อมข้อความ
            if ($request->routeIs('auth.login', 'auth.register')) {
                return redirect('/')
                    ->with('open_modal', $request->routeIs('auth.login') ? 'login' : 'register')
                    ->withErrors(['session' => $message]);
            }

            return redirect()->back()->with('error', $message);
        });
    })->create();

