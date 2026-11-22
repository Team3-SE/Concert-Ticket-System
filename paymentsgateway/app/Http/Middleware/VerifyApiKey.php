<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $token  = $request->bearerToken();
        $apiKey = config('paymock.api_key');

        if (! $token || ! hash_equals($apiKey, $token)) {
            return response()->json(['error' => 'invalid_api_key', 'message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
