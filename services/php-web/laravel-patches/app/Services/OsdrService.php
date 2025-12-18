<?php

namespace App\Services;

/**
 * Сервис для работы с OSDR датасетами
 */
class OsdrService extends RustApiService
{
    /**
     * Получить список датасетов с пагинацией
     */
    public function getList(int $page = 1, int $perPage = 20): array
    {
        return $this->get('/osdr/list', [
            'page' => $page,
            'per_page' => $perPage
        ]);
    }

    /**
     * Получить статистику по датасетам
     */
    public function getStats(): array
    {
        return $this->get('/osdr/stats');
    }

    /**
     * Синхронизировать датасеты с NASA API
     */
    public function sync(): array
    {
        return $this->get('/osdr/sync', [], false); // без кеша
    }

    /**
     * Фильтровать датасеты
     */
    public function filter(array $filters = []): array
    {
        $list = $this->getList();
        $items = $list['data'] ?? [];

        // Фильтр по типу
        if (!empty($filters['type'])) {
            $items = array_filter($items, function($item) use ($filters) {
                return stripos($item['data_type'] ?? '', $filters['type']) !== false;
            });
        }

        // Фильтр по факторам
        if (!empty($filters['factors'])) {
            $items = array_filter($items, function($item) use ($filters) {
                $factors = $item['factors_json'] ?? [];
                if (!is_array($factors)) {
                    $factors = json_decode($factors, true) ?? [];
                }
                return !empty(array_intersect($factors, (array)$filters['factors']));
            });
        }

        // Поиск по названию
        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $items = array_filter($items, function($item) use ($search) {
                $title = strtolower($item['title'] ?? '');
                return stripos($title, $search) !== false;
            });
        }

        return array_values($items);
    }

    /**
     * Сортировка датасетов
     */
    public function sort(array $items, string $field = 'created_at', string $direction = 'desc'): array
    {
        usort($items, function($a, $b) use ($field, $direction) {
            $aVal = $a[$field] ?? '';
            $bVal = $b[$field] ?? '';
            
            $result = $aVal <=> $bVal;
            return $direction === 'desc' ? -$result : $result;
        });

        return $items;
    }
}
