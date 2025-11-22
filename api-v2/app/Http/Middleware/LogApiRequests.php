<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Log the request
        Log::channel('api')->info('API Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $request->user()?->user_id,
        ]);

        $response = $next($request);

        // Calculate response time
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);

        // Log the response
        Log::channel('api')->info('API Response', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->getStatusCode(),
            'response_time_ms' => $responseTime,
            'user_id' => $request->user()?->user_id,
        ]);

        // Log slow requests (> 1 second)
        if ($responseTime > 1000) {
            Log::channel('performance')->warning('Slow API Request', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'response_time_ms' => $responseTime,
                'user_id' => $request->user()?->user_id,
            ]);
        }

        return $response;
    }
}
