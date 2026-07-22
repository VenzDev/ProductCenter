<?php

use Illuminate\Support\Facades\Route;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\APC;

Route::get('/', function () {
    return response()->json(['message' => 'hello world']);
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('/metrics', function () {
    $registry = new CollectorRegistry(new APC());
    $renderer = new RenderTextFormat();

    return response($renderer->render($registry->getMetricFamilySamples()))
        ->header('Content-Type', RenderTextFormat::MIME_TYPE);
});
