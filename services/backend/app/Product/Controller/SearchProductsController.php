<?php

declare(strict_types=1);

namespace App\Product\Controller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Product\Resource\ProductResource;
use App\Product\Search\ProductSearcher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchProductsController extends Controller
{
    public function __construct(private readonly ProductSearcher $searcher) {}

    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'max:200'],
        ]);

        $ids = $this->searcher->search($data['q']);

        $products = Product::query()->with('category')->whereIn('id', $ids)->get()
            ->sortBy(fn (Product $product) => array_search($product->id, $ids, true))
            ->values();

        return ProductResource::collection($products);
    }
}
