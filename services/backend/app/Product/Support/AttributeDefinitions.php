<?php

declare(strict_types=1);

namespace App\Product\Support;

use App\Models\Attribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Once;

class AttributeDefinitions
{
    private const string CACHE_KEY = 'attribute-definitions';

    /**
     * @return Collection<string, Attribute>
     */
    public static function all(): Collection
    {
        return once(function () {
            // Cache raw attribute rows, not hydrated models: config('cache.serializable_classes')
            // defaults to false, so the redis store's unserialize() rejects any cached object
            // and silently hands back __PHP_Incomplete_Class instead. Rehydrating from plain
            // arrays sidesteps that guardrail rather than loosening it.
            $rows = Cache::rememberForever(
                self::CACHE_KEY,
                fn () => Attribute::query()->get()->map->getAttributes()->all(),
            );

            return Attribute::hydrate($rows)->keyBy('key');
        });
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);

        // Also drop the per-request once() memo: without this, code that already
        // called all() earlier in the same request (e.g. a test that reads it, then
        // creates an Attribute, then reads it again) would keep seeing the stale
        // in-process value even though Redis was just invalidated.
        Once::flush();
    }
}
