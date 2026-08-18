<?php

use App\Auth\Admin\Controller\MicrosoftAuthController;
use Illuminate\Support\Facades\Route;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\APC;

Route::get('/', function () {
    return response()->json(['message' => 'hello world']);
});

Route::get('/auth/microsoft/redirect', [MicrosoftAuthController::class, 'redirect']);
Route::get('/auth/microsoft/callback', [MicrosoftAuthController::class, 'callback']);

// The dashboard itself (/admin) is now served by the Filament panel (AdminPanelProvider),
// which reuses this same 'admin' guard — see app/Providers/Filament/AdminPanelProvider.php.
// Named filament.admin.auth.logout so Filament's own UI (which looks up that route name
// for its logout link) resolves to our controller instead of registering a conflicting one.
Route::middleware('auth:admin')->group(function () {
    Route::post('/admin/logout', [MicrosoftAuthController::class, 'logout'])->name('filament.admin.auth.logout');
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
