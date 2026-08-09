<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\MicrosoftAuthController;
use Illuminate\Support\Facades\Route;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\APC;

Route::get('/', function () {
    return response()->json(['message' => 'hello world']);
});

Route::get('/auth/microsoft/redirect', [MicrosoftAuthController::class, 'redirect']);
Route::get('/auth/microsoft/callback', [MicrosoftAuthController::class, 'callback']);

Route::middleware('auth:admin')->group(function () {
    Route::get('/admin', [DashboardController::class, 'index']);
    Route::post('/admin/logout', [MicrosoftAuthController::class, 'logout']);
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('/metrics', function () {
    $registry = new CollectorRegistry(new APC);
    $renderer = new RenderTextFormat;

    return response($renderer->render($registry->getMetricFamilySamples()))
        ->header('Content-Type', RenderTextFormat::MIME_TYPE);
});
