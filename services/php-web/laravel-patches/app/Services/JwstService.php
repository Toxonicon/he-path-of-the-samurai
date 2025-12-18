<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Сервис для работы с JWST данными
 */
class JwstService
{
    protected string $baseUrl = 'https://api.jwstapi.com';
    protected int $timeout = 15;

    /**
     * Получить изображения по типу
     */
    public function getImages(string $type = 'jpg', int $page = 1, int $perPage = 24): array
    {
        $endpoint = "/all/type/{$type}";
        
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->baseUrl . $endpoint, [
                    'page' => $page,
                    'perPage' => $perPage
                ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            \Log::error('JWST API error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);
        }

        return [];
    }

    /**
     * Получить изображения по suffix
     */
    public function getBySuffix(string $suffix, int $page = 1, int $perPage = 24): array
    {
        $endpoint = "/all/suffix/" . ltrim($suffix, '/');
        
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->baseUrl . $endpoint, [
                    'page' => $page,
                    'perPage' => $perPage
                ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            \Log::error('JWST API error', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Получить изображения по программе
     */
    public function getByProgram(string $programId, int $page = 1, int $perPage = 24): array
    {
        $endpoint = "/program/id/{$programId}";
        
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->baseUrl . $endpoint, [
                    'page' => $page,
                    'perPage' => $perPage
                ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            \Log::error('JWST API error', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Нормализовать элементы для галереи
     */
    public function normalizeForGallery(array $items, string $instrumentFilter = ''): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (!is_array($item)) continue;

            // Выбираем URL изображения
            $url = $this->pickImageUrl($item);
            if (!$url) continue;

            // Получаем список инструментов
            $instruments = [];
            foreach (($item['details']['instruments'] ?? []) as $inst) {
                if (is_array($inst) && !empty($inst['instrument'])) {
                    $instruments[] = strtoupper($inst['instrument']);
                }
            }

            // Фильтр по инструменту
            if ($instrumentFilter && $instruments && !in_array(strtoupper($instrumentFilter), $instruments, true)) {
                continue;
            }

            $normalized[] = [
                'url' => $url,
                'observation_id' => (string)($item['observation_id'] ?? $item['observationId'] ?? ''),
                'program' => (string)($item['program'] ?? ''),
                'suffix' => (string)($item['details']['suffix'] ?? $item['suffix'] ?? ''),
                'instruments' => $instruments,
                'caption' => $this->buildCaption($item),
            ];
        }

        return $normalized;
    }

    /**
     * Выбрать валидный URL изображения
     */
    protected function pickImageUrl(array $item): ?string
    {
        $candidates = [
            $item['location'] ?? null,
            $item['url'] ?? null,
            $item['thumbnail'] ?? null,
        ];

        foreach ($candidates as $url) {
            if (is_string($url) && preg_match('~\.(jpg|jpeg|png)(\?.*)?$~i', $url)) {
                return $url;
            }
        }

        return null;
    }

    /**
     * Построить подпись для изображения
     */
    protected function buildCaption(array $item): string
    {
        $parts = [];
        
        $id = $item['observation_id'] ?? $item['id'] ?? '';
        if ($id) $parts[] = $id;
        
        $program = $item['program'] ?? '';
        if ($program) $parts[] = "P{$program}";
        
        $suffix = $item['details']['suffix'] ?? $item['suffix'] ?? '';
        if ($suffix) $parts[] = $suffix;

        return implode(' · ', $parts);
    }
}
