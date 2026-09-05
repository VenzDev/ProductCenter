<?php

declare(strict_types=1);

namespace App\Product\Search;

use App\Models\Product;
use App\Product\Search\Search\ProductFilterSearchResult;
use Illuminate\Pagination\LengthAwarePaginator;

readonly class ProductSearchPage
{
    /**
     * @param  LengthAwarePaginator<int, Product>  $paginator
     */
    public function __construct(
        public ProductFilterSearchResult $result,
        public LengthAwarePaginator $paginator,
    ) {}
}
