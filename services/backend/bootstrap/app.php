<?php

declare(strict_types=1);

use App\Http\Middleware\PrometheusMetrics;
use App\Product\Search\Console\InstallProductSearchIndexCommand;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        InstallProductSearchIndexCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        // ALB terminates TLS and forwards plain HTTP to the pod — without trusting its
        // X-Forwarded-Proto, Request::isSecure() is false and Filament emits http:// asset
        // URLs, which browsers block as mixed content on an https:// page. ALB's IP isn't
        // fixed (dynamically provisioned per Ingress, see docs/runbook.md step 9), hence '*'.
        $middleware->trustProxies(at: '*');
        $middleware->append(PrometheusMetrics::class);
        // /admin is a real (Blade) page, so a guest gets sent through the Microsoft
        // login flow. Everything else (the JSON api/* auth) has no login page to
        // redirect to, so it stays a plain 401.
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('admin*') ? '/auth/microsoft/redirect' : null
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Everything except /admin is JSON-only — no Blade error views exist for it.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => ! $request->is('admin*'));
    })->create();
