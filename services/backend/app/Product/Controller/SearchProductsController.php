<?php

declare(strict_types=1);

namespace App\Product\Controller;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Category;
use App\Product\Request\SearchProductsRequest;
use App\Product\Resource\ProductResource;
use App\Product\Search\Formatter\AttributeFacetFormatter;
use App\Product\Search\Formatter\CategoryFacetFormatter;
use App\Product\Search\ProductSearchOrchestrator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchProductsController extends Controller
{
    public function __construct(private readonly ProductSearchOrchestrator $orchestrator) {}

    /**
     * Full-text product search, optionally narrowed to one category. The categories
     * matched by `q` (with counts) are always returned under `filters.categories` — mirrors
     * a typical storefront search page, which lists matching categories alongside results
     * before the user picks one. Attribute filter options only appear once `category_id`
     * narrows the search, since attribute sets are per-category and aren't comparable
     * across a broad, multi-category result set.
     *
     * Query params: `q` (required), `category_id`, `price_min`, `price_max`,
     * `attr[<key>][]=<value>` (repeatable per attribute, only applied once `category_id`
     * is set), `sort` (relevance, the default, price_asc, or price_desc), `page`.
     */
    public function __invoke(SearchProductsRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();

        $category = isset($data['category_id']) ? Category::find((int) $data['category_id']) : null;
        $categoryIds = $category?->selfAndChildIds() ?? [];

        $filterableAttributes = $category !== null
            ? Attribute::filterableForCategories($categoryIds)
            : collect();

        $searchPage = $this->orchestrator->search(
            request: $request,
            query: $data['q'],
            categoryIds: $categoryIds,
            filterableAttributes: $filterableAttributes,
            requestedAttributeFilters: $data['attr'] ?? [],
            priceMin: isset($data['price_min']) ? (int) $data['price_min'] : null,
            priceMax: isset($data['price_max']) ? (int) $data['price_max'] : null,
            sort: $data['sort'] ?? 'relevance',
            page: max((int) ($data['page'] ?? 1), 1),
        );

        return ProductResource::collection($searchPage->paginator)->additional([
            'filters' => [
                'categories' => CategoryFacetFormatter::format($searchPage->result->categoryBuckets),
                'price' => $searchPage->result->priceStats,
                'attributes' => $filterableAttributes->map(fn (Attribute $attribute) => [
                    'key' => $attribute->key,
                    'name' => $attribute->name,
                    'options' => AttributeFacetFormatter::format($attribute, $searchPage->result->attributeBuckets[$attribute->key] ?? []),
                ])->values(),
            ],
        ]);
    }
}
