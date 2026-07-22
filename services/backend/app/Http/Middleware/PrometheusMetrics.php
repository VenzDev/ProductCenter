<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\APC;
use Symfony\Component\HttpFoundation\Response;

class PrometheusMetrics
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $registry = new CollectorRegistry(new APC());
        $method = $request->method();
        $path = '/'.ltrim($request->route()?->uri() ?? 'unmatched', '/');

        $registry
            ->getOrRegisterCounter('', 'http_requests_total', 'Total number of HTTP requests', ['method', 'path', 'status'])
            ->inc([$method, $path, (string) $response->getStatusCode()]);

        $registry
            ->getOrRegisterHistogram('', 'http_request_duration_seconds', 'HTTP request duration in seconds', ['method', 'path'])
            ->observe(microtime(true) - $start, [$method, $path]);

        return $response;
    }
}
