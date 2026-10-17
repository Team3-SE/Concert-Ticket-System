<?php

namespace App\Http\Middleware;

use App\Support\MockMode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chaos ชั้นใหญ่สุด — ครอบคลุม API (/v1/*) และ Checkout UI (/checkout/*)
 *
 * แหล่งที่มา: Admin setting `chaos_mode` เท่านั้น
 * รองรับ Gateway unavailable (503), slow response และ rate limit.
 * ความผิดพลาดที่ส่งถึงลูกค้ายังคงถูกแปลงเป็นข้อความเชิงธุรกิจโดยระบบคอนเสิร์ต
 */
class ChaosMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $mode = MockMode::chaosMode();
        $name = MockMode::name($mode);

        $isJson = $request->expectsJson() || $request->is('v1/*');

        switch ($name) {
            case 'down':
                if ($isJson) {
                    return response()->json([
                        'error'   => 'service_unavailable',
                        'message' => 'Gateway มีปัญหา โปรดลองอีกครั้ง',
                    ], 503);
                }
                return response()->view('errors.503', [], 503);

            case 'rate_limit':
                if ($isJson) {
                    return response()->json([
                        'error' => 'too_many_requests',
                        'message' => 'Too many requests. Please retry after 30 seconds.',
                    ], 429)->header('Retry-After', '30');
                }
                return response()->view('errors.503', [], 429)
                    ->header('Retry-After', '30');

            case 'slow':
                $seconds = max(0, min(3600, (int) (explode(':', $mode, 2)[1] ?? 5)));
                sleep($seconds);
                break;
        }

        return $next($request);
    }
}
