<?php

declare(strict_types=1);

namespace App\Product\Search;

use App\Models\Attribute;
use App\Product\Search\Hydrator\ProductHydrator;
use App\Product\Search\Search\ProductFilterSearcher;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Shared query/hydrate/paginate pipeline behind the two product listing endpoints
 * (full-text search and per-category browsing): both narrow by the same price/attribute
 * filters, run the same OpenSearch query, and paginate the same hydrated Eloquent
 * models. Callers differ only in how they resolve category/query context and how they
 * shape their own `filters` response payload from the returned facets — so those stay
 * in the controllers, not here.
 */
readonly class ProductSearchOrchestrator
{
    private const int PER_PAGE = 15;

    public function __construct(
        private ProductFilterSearcher $searcher,
        private ProductHydrator $hydrator,
    ) {}

    /**
     * @param  list<int>  $categoryIds
     * @param  Collection<int, Attribute>  $filterableAttributes
     * @param  array<string, list<string>>  $requestedAttributeFilters  raw `attr` request input, not yet restricted to filterable keys
     */
    public function search(
        Request $request,
        ?string $query,
        array $categoryIds,
        Collection $filterableAttributes,
        array $requestedAttributeFilters,
        ?int $priceMin,
        ?int $priceMax,
        string $sort,
        int $page,
    ): ProductSearchPage {
        /** @var list<string> $filterableKeys */
        $filterableKeys = $filterableAttributes->pluck('key')->values()->all();
        $selectedAttributeFilters = array_intersect_key($requestedAttributeFilters, array_flip($filterableKeys));

        $from = ($page - 1) * self::PER_PAGE;

        $result = $this->searcher->search(
            query: $query,
            categoryIds: $categoryIds,
            filterableAttributeKeys: $filterableKeys,
            selectedAttributeFilters: $selectedAttributeFilters,
            priceMin: $priceMin,
            priceMax: $priceMax,
            sort: $sort,
            from: $from,
            size: self::PER_PAGE,
        );

        $products = $this->hydrator->hydrate($result->ids);

        $paginator = new LengthAwarePaginator(
            $products,
            $result->total,
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return new ProductSearchPage($result, $paginator);
    }
}
