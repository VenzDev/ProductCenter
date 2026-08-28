<?php

declare(strict_types=1);

namespace App\Product\Search\Search;

use OpenSearch\Client;
use OpenSearch\Common\Exceptions\Missing404Exception;

/**
 * Builds and runs the product listing/search query: an optional free-text match plus
 * whichever category/price/attribute filters are active, alongside "multi-select
 * faceting" aggregations that report option counts as if every filter except the one
 * being faceted were applied — so picking a value narrows the results without making
 * sibling options (or sibling categories) disappear.
 *
 * The free-text match (if any) is the only thing in the main `query` — category, price,
 * and attribute filters all go through `post_filter` instead. OpenSearch narrows the
 * returned hits (and their total) by post_filter, but — unlike a query-level filter —
 * never lets it narrow what aggregations see. That's what makes per-facet scoping
 * possible: each facet's own `filter` agg re-applies every *other* active filter on top
 * of the text-query-only context, deliberately leaving its own filter out. A category
 * facet is always computed; callers that don't need it (browsing a fixed category) just
 * ignore ProductFilterSearchResult::$categoryBuckets.
 */
class ProductFilterSearcher
{
    private const string INDEX = 'products';

    public function __construct(private readonly Client $client) {}

    /**
     * @param  list<int>  $categoryIds  category ids the result must belong to (e.g. a category plus its children, so a parent category page includes their products); empty means no category restriction
     * @param  list<string>  $filterableAttributeKeys  attributes that can be faceted/filtered on (empty when $categoryIds is empty, since attribute sets are per-category)
     * @param  array<string, list<string>>  $selectedAttributeFilters  attribute key => selected values, already restricted to $filterableAttributeKeys
     */
    public function search(
        ?string $query,
        array $categoryIds,
        array $filterableAttributeKeys,
        array $selectedAttributeFilters,
        ?int $priceMin,
        ?int $priceMax,
        string $sort,
        int $from,
        int $size,
    ): ProductFilterSearchResult {
        $categoryFilter = $categoryIds !== [] ? ['terms' => ['category_id' => $categoryIds]] : null;
        $priceFilter = $this->priceRangeFilter($priceMin, $priceMax);
        $attributeFilters = $this->attributeFilters($selectedAttributeFilters);
        $postFilterClauses = array_values(array_filter([$categoryFilter, $priceFilter, ...array_values($attributeFilters)]));

        $sortClauses = $this->buildSort($sort);

        try {
            $response = $this->client->search([
                'index' => self::INDEX,
                'body' => array_filter([
                    // "bool" must stay a non-empty PHP array even with nothing to search
                    // by — an empty array here would serialize to JSON `[]`, not `{}`,
                    // which OpenSearch rejects; an empty `must` list is a valid, harmless
                    // "matches everything" bool query instead.
                    'query' => ['bool' => ['must' => $query !== null ? [$this->textMatchClause($query)] : []]],
                    'post_filter' => ['bool' => ['filter' => $postFilterClauses]],
                    'from' => $from,
                    'size' => $size,
                    'sort' => $sortClauses !== [] ? $sortClauses : null,
                    'track_total_hits' => true,
                    'aggs' => $this->buildAggregations($filterableAttributeKeys, $attributeFilters, $categoryFilter, $priceFilter),
                ], fn (mixed $value) => $value !== null),
            ]);
        } catch (Missing404Exception) {
            // No product has been indexed yet, so the index doesn't exist.
            return new ProductFilterSearchResult(ids: [], total: 0, categoryBuckets: [], attributeBuckets: [], priceStats: null);
        }

        /** @var list<array{_id: string}> $hits */
        $hits = $response['hits']['hits'];

        $attributeBuckets = [];
        foreach ($filterableAttributeKeys as $key) {
            $attributeBuckets[$key] = $response['aggregations']["facet_{$key}"]['values']['buckets'] ?? [];
        }

        $priceStats = $response['aggregations']['facet_price']['bounds'] ?? null;

        return new ProductFilterSearchResult(
            ids: array_map(fn (array $hit) => (int) $hit['_id'], $hits),
            total: (int) $response['hits']['total']['value'],
            categoryBuckets: $response['aggregations']['facet_category']['values']['buckets'] ?? [],
            attributeBuckets: $attributeBuckets,
            priceStats: $priceStats !== null && $priceStats['count'] > 0
                ? ['min' => (int) $priceStats['min'], 'max' => (int) $priceStats['max']]
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function textMatchClause(string $query): array
    {
        return [
            'multi_match' => [
                'query' => $query,
                'fields' => ['name.*', 'description.*'],
                'fuzziness' => 'AUTO',
            ],
        ];
    }

    /**
     * @return array{range: array{price_cents: array<string, int>}}|null
     */
    private function priceRangeFilter(?int $min, ?int $max): ?array
    {
        if ($min === null && $max === null) {
            return null;
        }

        $range = array_filter(['gte' => $min, 'lte' => $max], fn (?int $bound) => $bound !== null);

        return ['range' => ['price_cents' => $range]];
    }

    /**
     * @param  array<string, list<string>>  $selected
     * @return array<string, array{terms: array<string, list<string>>}>
     */
    private function attributeFilters(array $selected): array
    {
        $filters = [];
        foreach ($selected as $key => $values) {
            $filters[$key] = ['terms' => ["attributes.{$key}" => $values]];
        }

        return $filters;
    }

    /**
     * @param  list<string>  $filterableAttributeKeys
     * @param  array<string, array{terms: array<string, list<string>>}>  $attributeFilters
     * @param  array{terms: array<string, list<int>>}|null  $categoryFilter
     * @param  array{range: array{price_cents: array<string, int>}}|null  $priceFilter
     * @return array<string, mixed>
     */
    private function buildAggregations(array $filterableAttributeKeys, array $attributeFilters, ?array $categoryFilter, ?array $priceFilter): array
    {
        $aggs = [];

        $categoryScope = array_values(array_filter([$priceFilter, ...array_values($attributeFilters)]));
        $aggs['facet_category'] = [
            'filter' => ['bool' => ['filter' => $categoryScope]],
            'aggs' => ['values' => ['terms' => ['field' => 'category_id', 'size' => 50]]],
        ];

        foreach ($filterableAttributeKeys as $key) {
            $otherAttributeFilters = array_values(array_diff_key($attributeFilters, [$key => true]));
            $scope = array_values(array_filter([$categoryFilter, $priceFilter, ...$otherAttributeFilters]));

            $aggs["facet_{$key}"] = [
                'filter' => ['bool' => ['filter' => $scope]],
                'aggs' => [
                    'values' => ['terms' => ['field' => "attributes.{$key}", 'size' => 50]],
                ],
            ];
        }

        // The price facet excludes only the price filter itself, so the shown min/max
        // covers everything the current category/attribute selection matches, not just
        // the already-price-filtered subset.
        $priceScope = array_values(array_filter([$categoryFilter, ...array_values($attributeFilters)]));
        $aggs['facet_price'] = [
            'filter' => ['bool' => ['filter' => $priceScope]],
            'aggs' => ['bounds' => ['stats' => ['field' => 'price_cents']]],
        ];

        return $aggs;
    }

    /**
     * @return list<array<string, string>>
     */
    private function buildSort(string $sort): array
    {
        return match ($sort) {
            'price_asc' => [['price_cents' => 'asc']],
            'price_desc' => [['price_cents' => 'desc']],
            // 'relevance' (or no query at all): let OpenSearch use its natural order —
            // _score when a text query is present, index order otherwise.
            default => [],
        };
    }
}
