<?php

declare(strict_types=1);

namespace App\Product\Search\Formatter;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

/**
 * Formats a category's direct children into a facet list with product counts — pulled
 * from the searcher's always-computed, catalog-wide category aggregation, filtered down
 * here to just the given children.
 */
class SubcategoryFacetFormatter
{
    /**
     * @param  Collection<int, Category>  $children
     * @param  list<array{key: int, doc_count: int}>  $categoryBuckets
     * @return list<array{id: int, slug: string, name: string, count: int}>
     */
    public static function format(Collection $children, array $categoryBuckets): array
    {
        $counts = collect($categoryBuckets)->pluck('doc_count', 'key');

        /** @var list<array{id: int, slug: string, name: string, count: int}> $subcategories */
        $subcategories = $children->map(fn (Category $child) => [
            'id' => $child->id,
            'slug' => $child->slug,
            'name' => $child->name,
            'count' => (int) ($counts[$child->id] ?? 0),
        ])->values()->all();

        return $subcategories;
    }
}
