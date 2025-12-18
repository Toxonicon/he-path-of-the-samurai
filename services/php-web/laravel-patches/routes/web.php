<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OsdrController;
use App\Http\Controllers\ProxyController;
use App\Http\Controllers\AstroController;
use App\Http\Controllers\CmsController;
use App\Http\Controllers\IssController;
use App\Http\Controllers\LegacyController;
use App\Http\Middleware\ApiRateLimiter;
use App\Http\Middleware\CacheApiResponse;

Route::get('/', fn() => redirect('/dashboard'));

// Панели
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/osdr', [OsdrController::class, 'index']);
Route::get('/iss', [IssController::class, 'index'])->name('iss');
Route::get('/astronomy', fn() => view('astronomy'))->name('astronomy');

// API для ISS данных (cache 60 seconds)
Route::prefix('api/iss')->middleware([ApiRateLimiter::class . ':120,1', CacheApiResponse::class . ':60'])->group(function() {
    Route::get('/last', [ProxyController::class, 'last']);
    Route::get('/trend', [ProxyController::class, 'trend']);
    Route::get('/range', [ProxyController::class, 'range']);
});

// API для OSDR данных (cache 300 seconds)
Route::prefix('api/osdr')->middleware([ApiRateLimiter::class . ':60,1', CacheApiResponse::class . ':300'])->group(function() {
    Route::get('/list', [OsdrController::class, 'list']);
    Route::get('/stats', [OsdrController::class, 'stats']);
    Route::get('/sync', [OsdrController::class, 'sync']);
});

// JWST галерея (cache 3600 seconds)
Route::get('/api/jwst/feed', [DashboardController::class, 'jwstFeed'])
    ->middleware([ApiRateLimiter::class . ':30,1', CacheApiResponse::class . ':3600']);

// Астрономические события (cache 1800 seconds)
Route::middleware([ApiRateLimiter::class . ':60,1', CacheApiResponse::class . ':1800'])->group(function() {
    Route::get('/api/astro/events', [AstroController::class, 'events']);
    Route::get('/api/astronomy-events', [AstroController::class, 'events']);
});

// Pascal Legacy telemetry (cache 120 seconds)
Route::get('/legacy', [LegacyController::class, 'index'])->name('legacy');
Route::get('/legacy/stats', [LegacyController::class, 'stats'])->name('legacy.stats');
Route::get('/legacy/export', [LegacyController::class, 'exportXlsx'])->name('legacy.export');
Route::get('/api/legacy', [LegacyController::class, 'api'])
    ->middleware([ApiRateLimiter::class . ':60,1', CacheApiResponse::class . ':120']);

// CMS страницы
Route::get('/page/{slug}', [CmsController::class, 'page']);
