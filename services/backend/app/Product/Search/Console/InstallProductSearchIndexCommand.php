<?php

declare(strict_types=1);

namespace App\Product\Search\Console;

use App\Product\Search\Index\ProductSearchIndexManager;
use Illuminate\Console\Command;

class InstallProductSearchIndexCommand extends Command
{
    protected $signature = 'search:install-index';

    protected $description = 'Create the OpenSearch products index if needed, and (re-)apply its field mapping so it stays in sync with the schema';

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
