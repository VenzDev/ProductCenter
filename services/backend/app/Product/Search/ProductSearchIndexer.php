<?php

declare(strict_types=1);

namespace App\Product\Search;

use App\Models\Product;
use OpenSearch\Client;

class ProductSearchIndexer
{
    private const string INDEX = 'products';

    public function __construct(private readonly Client $client) {}

    public function index(Product $product): void
    {
        $this->client->index([
            'index' => self::INDEX,
            'id' => (string) $product->id,
            'body' => [
                // Indexed per-locale (spatie/laravel-translatable) so a query can match
                // either language without needing the per-language analyzer setup yet.
                'name' => $product->getTranslations('name'),
                'description' => $product->getTranslations('description'),
            ],
        ]);
    }

    public function delete(Product $product): void
    {
        $this->client->delete([
            'index' => self::INDEX,
            'id' => (string) $product->id,
            'client' => ['ignore' => [404]],
        ]);
    }
}
