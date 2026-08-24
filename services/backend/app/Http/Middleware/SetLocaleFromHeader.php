<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $primary = Str::of((string) $request->header('Accept-Language'))
            ->before(',')
            ->before('-')
            ->trim()
            ->lower()
            ->toString();

        if ($language = Language::tryFrom($primary)) {
            app()->setLocale($language->value);
        }

        return $next($request);
    }
}
