<?php

declare(strict_types=1);

namespace App\Product\Controller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Product\Resource\ProductResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * List products.
     *
     * Translatable fields (name, description) are returned in the language requested
     * via the `Accept-Language` header (`en` or `pl`), falling back to `en` otherwise.
     */
    public function index(): AnonymousResourceCollection
    {
        return ProductResource::collection(
            Product::query()->with('category')->paginate()
        );
    }

    /**
     * List the most recently created products.
     *
     * Same translation behavior as {@see index()}.
     */
    public function latest(): AnonymousResourceCollection
    {
        return ProductResource::collection(
            Product::query()->with('category')->orderByDesc('id')->take(4)->get()
        );
    }

    /**
     * Retrieve a single product.
     *
     * Translatable fields (name, description) are returned in the language requested
     * via the `Accept-Language` header (`en` or `pl`), falling back to `en` otherwise.
     */
    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load('category', 'images'));
    }
}
