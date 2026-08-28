<?php

declare(strict_types=1);

namespace App\Product\Search\Formatter;

use App\Models\Attribute;

/**
 * Formats an attribute's terms-aggregation buckets into labeled filter options — shared by
 * the category-browsing and search endpoints, which both build attribute facets off the
 * same ProductFilterSearcher aggregations.
 */
class AttributeFacetFormatter
{
    /**
     * @param  list<array{key: int|float|string, doc_count: int}>  $buckets
     * @return list<array{key: string, label: string, count: int}>
     */
    public static function format(Attribute $attribute, array $buckets): array
    {
        $labels = $attribute->translatedOptions();

        return array_map(fn (array $bucket) => [
            'key' => (string) $bucket['key'],
            'label' => $labels[(string) $bucket['key']] ?? (string) $bucket['key'],
            'count' => $bucket['doc_count'],
        ], $buckets);
    }
}
