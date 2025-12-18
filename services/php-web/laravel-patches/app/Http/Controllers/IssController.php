<?php

namespace App\Http\Controllers;

use App\Services\IssService;
use Illuminate\Http\Request;

class IssController extends Controller
{
    protected IssService $issService;

    public function __construct(IssService $issService)
    {
        $this->issService = $issService;
    }

    /**
     * Страница отслеживания МКС
     */
    public function index()
    {
        $current = $this->issService->getLastPosition();
        $metrics = $this->issService->getMetrics();
        $isVisible = $this->issService->isVisible();

        return view('iss', [
            'current' => $current,
            'metrics' => $metrics,
            'isVisible' => $isVisible,
            'last' => $current, // для обратной совместимости
            'trend' => [],
            'base' => getenv('RUST_BASE') ?: 'http://rust_iss:3000'
        ]);
    }
}
