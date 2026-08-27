<?php

declare(strict_types=1);

namespace App\Product\Search\Console;

use App\Product\Search\ProductSearchIndexManager;
use Illuminate\Console\Command;

class InstallProductSearchIndexCommand extends Command
{
    protected $signature = 'search:install-index';

    protected $description = 'Create the OpenSearch products index with its per-language mapping and analyzers, if it does not already exist';

    public function __construct(private readonly ProductSearchIndexManager $indexManager)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->indexManager->ensureIndexExists();

        $this->info('Product search index is ready.');

        return self::SUCCESS;
    }
}
