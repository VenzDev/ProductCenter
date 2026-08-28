<?php

declare(strict_types=1);

namespace App\Product\Search\Search;

readonly class ProductFilterSearchResult
{
    /**
     * @param  list<int>  $ids  matching product IDs for the current page, in sort order
     * @param  list<array{key: int, doc_count: int}>  $categoryBuckets  terms aggregation buckets on category_id; ignored by callers that already fix the category (e.g. category browsing)
     * @param  array<string, list<array{key: int|float|string, doc_count: int}>>  $attributeBuckets  attribute key => terms aggregation buckets
     * @param  array{min: int, max: int}|null  $priceStats  null when nothing matches the active filters
     */
    public function __construct(
        public array $ids,
        public int $total,
        public array $categoryBuckets,
        public array $attributeBuckets,
        public ?array $priceStats,
    ) {}
}
