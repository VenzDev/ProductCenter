<?php

declare(strict_types=1);

namespace App\Category\Controller;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Category;
use App\Product\Request\ListCategoryProductsRequest;
use App\Product\Resource\ProductResource;
use App\Product\Search\Formatter\AttributeFacetFormatter;
use App\Product\Search\Formatter\SubcategoryFacetFormatter;
use App\Product\Search\Hydrator\ProductHydrator;
use App\Product\Search\Search\ProductFilterSearcher;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryProductsController extends Controller
{
    private const int PER_PAGE = 15;

    public function __construct(
        private readonly ProductFilterSearcher $searcher,
        private readonly ProductHydrator $hydrator,
    ) {}

    /**
     * List a category's products — including its subcategories' products, if it has any —
     * filtered by price range and/or attribute values, with the available filter options
     * (and their counts) returned alongside under `filters`. `filters.subcategories` lists
     * each direct subcategory with its own product count — reflecting any active price or
     * attribute filters, same as the other facets — so a category page can offer further
     * drill-down.
     *
     * Query params: `price_min`, `price_max`, `attr[<key>][]=<value>` (repeatable per
     * attribute), `sort` (price_asc, the default, or price_desc), `page`.
     */
    public function index(ListCategoryProductsRequest $request, Category $category): AnonymousResourceCollection
    {
        $data = $request->validated();

        $categoryIds = $category->selfAndChildIds();
        $filterableAttributes = Attribute::filterableForCategories($categoryIds);

        /** @var list<string> $filterableKeys */
        $filterableKeys = $filterableAttributes->pluck('key')->values()->all();

        /** @var array<string, list<string>> $requestedAttributeFilters */
        $requestedAttributeFilters = $data['attr'] ?? [];
        $selectedAttributeFilters = array_intersect_key($requestedAttributeFilters, array_flip($filterableKeys));

        $page = max((int) ($data['page'] ?? 1), 1);
        $from = ($page - 1) * self::PER_PAGE;

        $result = $this->searcher->search(
            query: null,
            categoryIds: $categoryIds,
            filterableAttributeKeys: $filterableKeys,
            selectedAttributeFilters: $selectedAttributeFilters,
            priceMin: isset($data['price_min']) ? (int) $data['price_min'] : null,
            priceMax: isset($data['price_max']) ? (int) $data['price_max'] : null,
            sort: $data['sort'] ?? 'price_asc',
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

        return ProductResource::collection($paginator)->additional([
            'filters' => [
                'price' => $result->priceStats,
                'attributes' => $filterableAttributes->map(fn (Attribute $attribute) => [
                    'key' => $attribute->key,
                    'name' => $attribute->name,
                    'options' => AttributeFacetFormatter::format($attribute, $result->attributeBuckets[$attribute->key] ?? []),
                ])->values(),
                'subcategories' => SubcategoryFacetFormatter::format($category->children, $result->categoryBuckets),
            ],
        ]);
    }
}
