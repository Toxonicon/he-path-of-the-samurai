<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * Базовый сервис для работы с Rust API
 */
class RustApiService
{
    protected string $baseUrl;
    protected int $timeout;
    protected int $cacheMinutes;

    public function __construct()
    {
        $this->baseUrl = getenv('RUST_BASE') ?: 'http://rust_iss:3000';
        $this->timeout = 10;
        $this->cacheMinutes = 5;
    }

    /**
     * GET запрос к API с кешированием
     */
    protected function get(string $endpoint, array $params = [], bool $useCache = true): array
    {
        $cacheKey = 'api_' . md5($endpoint . json_encode($params));

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout($this->timeout)
                ->get($this->baseUrl . $endpoint, $params);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($useCache) {
                    Cache::put($cacheKey, $data, now()->addMinutes($this->cacheMinutes));
                }
                
                return $data;
            }
        } catch (\Exception $e) {
            \Log::error('Rust API error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);
        }

        return [];
    }

    /**
     * Очистить кеш для определённого эндпоинта
     */
    protected function clearCache(string $endpoint, array $params = []): void
    {
        $cacheKey = 'api_' . md5($endpoint . json_encode($params));
        Cache::forget($cacheKey);
    }
}
