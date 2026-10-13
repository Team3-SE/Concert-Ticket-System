<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ChaosMiddleware – ครอบคลุมทั้ง API (/v1/*) และ Checkout UI (/checkout/*)
 *
 * Scenarios (Header X-Mock-Scenario หรือ chaos_mode ใน settings):
 *   down           → 503 Service Unavailable
 *   rate_limit     → 429 Too Many Requests + Retry-After: 30
 *   bad_auth       → 401 Unauthorized
 *   bad_json       → 502 Bad Gateway
 *   error_rate:N   → random 500 ใน N% ของ requests
 *   slow:N         → sleep N วินาที แล้วทำงานต่อ
 *   timeout:N      → sleep N วินาที แล้วทำงานต่อ (payment ถูกสร้างจริง!)
 */
class ChaosMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // X-Mock-Scenario header มีลำดับความสำคัญสูงสุด
        $mode = $request->header('X-Mock-Scenario')
            ?? Setting::get('chaos_mode', 'normal');

        [$name, $arg] = array_pad(explode(':', (string) $mode, 2), 2, null);

        $isJson = $request->expectsJson() || $request->is('v1/*');

        switch ($name) {
            case 'down':
                if ($isJson) {
                    return response()->json([
                        'error'   => 'service_unavailable',
                        'message' => 'Payment gateway is temporarily unavailable',
                    ], 503);
                }
                return response()->view('errors.503', [
                    'message' => 'Payment gateway is down (Chaos Mode: down)'
                ], 503);

            case 'rate_limit':
                if ($isJson) {
                    return response()->json([
                        'error'   => 'too_many_requests',
                        'message' => 'Rate limit exceeded. Please retry after 30 seconds.',
                    ], 429)->header('Retry-After', '30');
                }
                return response(
                    '<h1>429 Too Many Requests</h1><p>Rate limit exceeded. Please retry after 30 seconds.</p>',
                    429
                )->header('Retry-After', '30');

            case 'bad_auth':
                return response()->json([
                    'error'   => 'invalid_api_key',
                    'message' => 'Authentication failed',
                ], 401);

            case 'bad_json':
                return response('<html><body>Bad Gateway (Chaos Mode: bad_json)</body></html>', 502)
                    ->header('Content-Type', 'text/html');

            case 'error_rate':
                $rate = max(0, min(100, (int) ($arg ?: 30)));
                if (random_int(1, 100) <= $rate) {
                    if ($isJson) {
                        return response()->json([
                            'error'   => 'internal_error',
                            'message' => 'An internal server error occurred',
                        ], 500);
                    }
                    return response('<h1>500 Internal Server Error (Chaos Simulation)</h1>', 500);
                }
                break;

            case 'slow':
            case 'timeout':
                $seconds = max(0, (int) ($arg ?: 5));
                sleep($seconds); // payment ยังถูกสร้าง เพียงแต่ช้า
                break;
        }

        return $next($request);
    }
}
