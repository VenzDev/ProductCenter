<?php

declare(strict_types=1);

namespace App\Product\Search\Index;

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
                'name' => $product->getTranslations('name'),
                'description' => $product->getTranslations('description'),
                'category_id' => $product->category_id,
                'price_cents' => $product->price_cents,
                'attributes' => $product->getAttributeCollection()->filterable()->getRaw(),
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
