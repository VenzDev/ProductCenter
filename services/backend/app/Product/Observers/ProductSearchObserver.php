<?php

declare(strict_types=1);

namespace App\Product\Observers;

use App\Models\Product;
use App\Product\Search\ProductSearchIndexer;

class ProductSearchObserver
{
    public function __construct(private readonly ProductSearchIndexer $indexer) {}

    public function saved(Product $product): void
    {
        $this->indexer->index($product);
    }

    public function deleted(Product $product): void
    {
        $this->indexer->delete($product);
    }
}
