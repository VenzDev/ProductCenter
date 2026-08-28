<?php

declare(strict_types=1);

namespace App\Product\Search\Formatter;

use App\Models\Category;

/**
 * Formats a search's category-aggregation buckets into a facet list with product counts
 * and names — looking each bucket's category up so a stale/deleted category_id never
 * leaks into the response.
 */
class CategoryFacetFormatter
{
    /**
     * @param  list<array{key: int, doc_count: int}>  $buckets
     * @return list<array{id: int, name: string, count: int}>
     */
    public static function format(array $buckets): array
    {
        $categoryIds = array_map(fn (array $bucket) => $bucket['key'], $buckets);
        $categories = Category::query()->whereIn('id', $categoryIds)->get()->keyBy('id');

        return array_values(array_filter(array_map(function (array $bucket) use ($categories) {
            $category = $categories->get($bucket['key']);

            return $category === null ? null : [
                'id' => $category->id,
                'name' => $category->name,
                'count' => $bucket['doc_count'],
            ];
        }, $buckets)));
    }
}
