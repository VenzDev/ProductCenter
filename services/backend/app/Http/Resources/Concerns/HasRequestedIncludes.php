<?php

declare(strict_types=1);

namespace App\Http\Resources\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait HasRequestedIncludes
{
    /**
     * @return array<int, string>
     */
    protected function requestedIncludes(Request $request): array
    {
        return Str::of((string) $request->query('include'))
            ->explode(',')
            ->map(fn (string $include) => trim($include))
            ->filter()
            ->all();
    }
}
