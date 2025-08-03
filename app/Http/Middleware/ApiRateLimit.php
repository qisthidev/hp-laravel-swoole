<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $endpoint = $this->getEndpointType($request);
        
        $limit = $this->getRateLimit($endpoint);
        $decayMinutes = 60; // 1 hour

        $key = "api_rate_limit:{$endpoint}:{$ip}";

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $retryAfter = RateLimiter::availableIn($key);
            
            return response()->json([
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Rate limit exceeded. Please try again later.',
                    'retry_after' => $retryAfter,
                ],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'request_id' => uniqid('req_'),
                ],
            ], 429)->header('Retry-After', $retryAfter);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', $limit);
        $response->headers->set('X-RateLimit-Remaining', RateLimiter::remaining($key, $limit));
        $response->headers->set('X-RateLimit-Reset', time() + ($decayMinutes * 60));

        return $response;
    }

    /**
     * Get the endpoint type for rate limiting
     */
    private function getEndpointType(Request $request): string
    {
        $path = $request->path();
        
        if (str_contains($path, '/search') || str_contains($path, '/autocomplete')) {
            return 'search';
        }
        
        if (str_contains($path, '/export')) {
            return 'export';
        }
        
        return 'public';
    }

    /**
     * Get rate limit based on endpoint type
     */
    private function getRateLimit(string $endpoint): int
    {
        return match ($endpoint) {
            'search' => 500,    // 500 requests per hour for search endpoints
            'export' => 100,    // 100 requests per hour for export endpoints
            default => 1000,    // 1000 requests per hour for public endpoints
        };
    }
}