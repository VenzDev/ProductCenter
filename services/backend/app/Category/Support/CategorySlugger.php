<?php

declare(strict_types=1);

namespace App\Category\Support;

use Illuminate\Support\Str;

class CategorySlugger
{
    public static function slug(string $name, ?string $parentSlug = null): string
    {
        $slug = Str::slug($name);

        return $parentSlug ? "{$parentSlug}/{$slug}" : $slug;
    }
}
