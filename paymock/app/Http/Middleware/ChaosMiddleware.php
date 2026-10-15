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
 * (header X-Mock-Scenario ยังใช้ได้เฉพาะค่า chaos เพื่อเทสภายใน PayMock ไม่ใช่ API ของ merchant)
 */
class ChaosMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $mode = MockMode::chaosMode();
        $header = $request->header('X-Mock-Scenario');
        if (MockMode::isChaos($header) && $header !== null && $header !== '') {
            $mode = $header;
        }

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
                sleep($seconds);
                break;
        }

        return $next($request);
    }
}
