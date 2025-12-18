<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OsdrController;
use App\Http\Controllers\ProxyController;
use App\Http\Controllers\AstroController;
use App\Http\Controllers\CmsController;
use App\Http\Controllers\IssController;

Route::get('/', fn() => redirect('/dashboard'));

// Панели
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/osdr', [OsdrController::class, 'index']);
Route::get('/iss', [IssController::class, 'index'])->name('iss');
Route::get('/astronomy', fn() => view('astronomy'))->name('astronomy');

// API для ISS данных
Route::prefix('api/iss')->group(function() {
    Route::get('/last', [ProxyController::class, 'last']);
    Route::get('/trend', [ProxyController::class, 'trend']);
    Route::get('/range', [ProxyController::class, 'range']);
});

// API для OSDR данных
Route::prefix('api/osdr')->group(function() {
    Route::get('/list', [OsdrController::class, 'list']);
    Route::get('/stats', [OsdrController::class, 'stats']);
    Route::get('/sync', [OsdrController::class, 'sync']);
});

// JWST галерея
Route::get('/api/jwst/feed', [DashboardController::class, 'jwstFeed']);

// Астрономические события
Route::get('/api/astro/events', [AstroController::class, 'events']);
Route::get('/api/astronomy-events', [AstroController::class, 'events']);

// CMS страницы
Route::get('/page/{slug}', [CmsController::class, 'page']);
