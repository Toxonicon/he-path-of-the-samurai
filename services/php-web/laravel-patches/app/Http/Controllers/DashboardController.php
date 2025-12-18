<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IssService;
use App\Services\JwstService;

class DashboardController extends Controller
{
    protected IssService $issService;
    protected JwstService $jwstService;

    public function __construct(IssService $issService, JwstService $jwstService)
    {
        $this->issService = $issService;
        $this->jwstService = $jwstService;
    }

    public function index()
    {
        $iss = $this->issService->getLastPosition();
        $metrics = $this->issService->getMetrics();

        return view('dashboard', [
            'iss' => $iss,
            'trend' => [],
            'jw_gallery' => [],
            'jw_observation_raw' => [],
            'jw_observation_summary' => [],
            'jw_observation_images' => [],
            'jw_observation_files' => [],
            'metrics' => $metrics,
        ]);
    }

    /**
     * /api/jwst/feed — серверный прокси/нормализатор JWST картинок.
     */
    public function jwstFeed(Request $r)
    {
        $source = $r->query('source', 'jpg');
        $suffix = trim((string)$r->query('suffix', ''));
        $program = trim((string)$r->query('program', ''));
        $instrument = strtoupper(trim((string)$r->query('instrument', '')));
        $page = max(1, (int)$r->query('page', 1));
        $perPage = max(1, min(60, (int)$r->query('perPage', 24)));

        // Получаем данные из JWST API
        $rawItems = [];
        if ($source === 'suffix' && $suffix !== '') {
            $response = $this->jwstService->getBySuffix($suffix, $page, $perPage);
        } elseif ($source === 'program' && $program !== '') {
            $response = $this->jwstService->getByProgram($program, $page, $perPage);
        } else {
            $response = $this->jwstService->getImages('jpg', $page, $perPage);
        }

        $rawItems = $response['body'] ?? $response['data'] ?? (is_array($response) ? $response : []);

        // Нормализуем для фронтенда
        $items = $this->jwstService->normalizeForGallery($rawItems, $instrument);

        return response()->json([
            'source' => $source,
            'count' => count($items),
            'items' => $items,
        ]);
    }
}
