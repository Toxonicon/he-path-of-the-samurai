<?php

namespace App\Services;

/**
 * Сервис для работы с данными МКС
 */
class IssService extends RustApiService
{
    /**
     * Получить последнюю позицию МКС
     */
    public function getLastPosition(): array
    {
        return $this->get('/last');
    }

    /**
     * Получить последние N позиций
     */
    public function getLastPositions(int $limit = 10): array
    {
        return $this->get('/last', ['limit' => $limit]);
    }

    /**
     * Получить тренд движения МКС
     */
    public function getTrend(int $hours = 24): array
    {
        return $this->get('/iss/trend', ['hours' => $hours]);
    }

    /**
     * Получить позиции за временной диапазон
     */
    public function getRange(string $from, string $to): array
    {
        return $this->get('/iss/range', [
            'from' => $from,
            'to' => $to
        ]);
    }

    /**
     * Получить метрики для дашборда
     */
    public function getMetrics(): array
    {
        $last = $this->getLastPosition();
        $payload = $last['payload'] ?? [];

        return [
            'velocity' => $payload['velocity'] ?? null,
            'altitude' => $payload['altitude'] ?? null,
            'latitude' => $payload['latitude'] ?? null,
            'longitude' => $payload['longitude'] ?? null,
            'timestamp' => $payload['timestamp'] ?? null,
            'visibility' => $payload['visibility'] ?? null,
        ];
    }

    /**
     * Проверить, виден ли МКС в данный момент
     */
    public function isVisible(): bool
    {
        $metrics = $this->getMetrics();
        return ($metrics['visibility'] ?? '') === 'daylight';
    }
}
