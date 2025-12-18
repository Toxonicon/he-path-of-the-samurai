<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class CacheApiResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $ttl = 60): Response
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        $cacheKey = $this->getCacheKey($request);

        try {
            // Try to get cached response
            $cachedResponse = Redis::get($cacheKey);
            
            if ($cachedResponse !== null) {
                $data = json_decode($cachedResponse, true);
                
                return response()->json($data)
                    ->header('X-Cache', 'HIT')
                    ->header('X-Cache-TTL', Redis::ttl($cacheKey));
            }
        } catch (\Exception $e) {
            // If Redis fails, continue without cache
            \Log::warning('Redis cache failed: ' . $e->getMessage());
        }

        // Get fresh response
        $response = $next($request);

        // Cache the response if it's successful
        if ($response->isSuccessful() && $response instanceof \Illuminate\Http\JsonResponse) {
            try {
                Redis::setex($cacheKey, $ttl, json_encode($response->getData()));
                $response->header('X-Cache', 'MISS');
            } catch (\Exception $e) {
                \Log::warning('Redis cache write failed: ' . $e->getMessage());
            }
        }

        return $response;
    }

    /**
     * Generate cache key from request
     */
    protected function getCacheKey(Request $request): string
    {
        $path = $request->path();
        $query = $request->query();
        ksort($query);
        
        return 'api_cache:' . md5($path . '?' . http_build_query($query));
    }
}
