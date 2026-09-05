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
use App\Product\Search\ProductSearchOrchestrator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryProductsController extends Controller
{
    public function __construct(private readonly ProductSearchOrchestrator $orchestrator) {}

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

        $searchPage = $this->orchestrator->search(
            request: $request,
            query: null,
            categoryIds: $categoryIds,
            filterableAttributes: $filterableAttributes,
            requestedAttributeFilters: $data['attr'] ?? [],
            priceMin: isset($data['price_min']) ? (int) $data['price_min'] : null,
            priceMax: isset($data['price_max']) ? (int) $data['price_max'] : null,
            sort: $data['sort'] ?? 'price_asc',
            page: max((int) ($data['page'] ?? 1), 1),
        );

        return ProductResource::collection($searchPage->paginator)->additional([
            'filters' => [
                'price' => $searchPage->result->priceStats,
                'attributes' => $filterableAttributes->map(fn (Attribute $attribute) => [
                    'key' => $attribute->key,
                    'name' => $attribute->name,
                    'options' => AttributeFacetFormatter::format($attribute, $searchPage->result->attributeBuckets[$attribute->key] ?? []),
                ])->values(),
                'subcategories' => SubcategoryFacetFormatter::format($category->children, $searchPage->result->categoryBuckets),
            ],
        ]);
    }
}
