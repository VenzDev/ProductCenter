<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use App\Product\Search\Index\ProductSearchIndexer;
use Illuminate\Console\Command;

class ReindexProducts extends Command
{
    protected $signature = 'products:reindex';

    protected $description = 'Rebuild the OpenSearch product search index from the database';

    public function handle(ProductSearchIndexer $indexer): int
    {
        $count = 0;

        Product::query()->chunkById(100, function ($products) use ($indexer, &$count) {
            foreach ($products as $product) {
                $indexer->index($product);
                $count++;
            }
        });

        $this->info("Reindexed {$count} product(s).");

        return self::SUCCESS;
    }
}
