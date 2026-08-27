<?php

declare(strict_types=1);

namespace App\Product\Search;

use OpenSearch\Client;
use OpenSearch\Common\Exceptions\Missing404Exception;

class ProductSearcher
{
    private const string INDEX = 'products';

    public function __construct(private readonly Client $client) {}

    /**
     * @return list<int> matching product IDs, most relevant first
     */
    public function search(string $query): array
    {
        try {
            $response = $this->client->search([
                'index' => self::INDEX,
                'body' => [
                    'query' => [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => ['name.*', 'description.*'],
                            'fuzziness' => 'AUTO',
                        ],
                    ],
                ],
            ]);
        } catch (Missing404Exception) {
            // No product has been indexed yet, so the index doesn't exist.
            return [];
        }

        /** @var list<array{_id: string}> $hits */
        $hits = $response['hits']['hits'];

        return array_map(fn (array $hit) => (int) $hit['_id'], $hits);
    }
}
