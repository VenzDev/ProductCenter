<?php

declare(strict_types=1);

namespace App\Product\Search\Hydrator;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

/**
 * Turns a ProductFilterSearcher result's product IDs back into full Product models,
 * preserving OpenSearch's own order (relevance for a text search, price for a sorted
 * category browse) — an ORDER BY can't do this, since it's not a column on the row.
 */
class ProductHydrator
{
    /**
     * @param  list<int>  $ids  in the order they should come back in
     * @return Collection<int, Product>
     */
    public function hydrate(array $ids): Collection
    {
        $products = Product::query()->with('category')->whereIn('id', $ids)->get();

        return $products
            ->sortBy(fn (Product $product) => array_search($product->id, $ids, true))
            ->values();
    }
}
