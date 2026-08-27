<?php

declare(strict_types=1);

namespace App\Product\Search;

use App\Product\Search\Language\SupportedSearchLanguages;
use OpenSearch\Client;

/**
 * Creates the "products" OpenSearch index with its per-language mapping and analyzers.
 * Kept separate from ProductSearchIndexer (which only ever pushes/removes documents) so
 * indexing a single product never carries the cost of an index-existence check — index
 * provisioning is a deploy-time step, run once via the search:install-index command.
 */
class ProductSearchIndexManager
{
    private const string INDEX = 'products';

    public function __construct(
        private readonly Client $client,
        private readonly SupportedSearchLanguages $languages,
    ) {}

    public function ensureIndexExists(): void
    {
        if ($this->client->indices()->exists(['index' => self::INDEX])) {
            return;
        }

        $this->client->indices()->create([
            'index' => self::INDEX,
            'body' => [
                'settings' => ['analysis' => $this->buildAnalysisSettings()],
                'mappings' => ['properties' => $this->buildProperties()],
            ],
        ]);
    }

    /**
     * @return array{filter: array<string, array<string, mixed>>, analyzer: array<string, array<string, mixed>>}
     */
    private function buildAnalysisSettings(): array
    {
        $filters = [];
        $analyzers = [];

        foreach ($this->languages->all() as $language) {
            $filters = [...$filters, ...$language->filters()];
            $analyzers[$language->analyzerName()] = $language->analyzer();
        }

        return ['filter' => $filters, 'analyzer' => $analyzers];
    }

    /**
     * name/description are translatable, stored as a {locale: text} object — one
     * sub-field per supported language, each analyzed with that language's analyzer.
     *
     * @return array<string, mixed>
     */
    private function buildProperties(): array
    {
        $localeProperties = [];

        foreach ($this->languages->all() as $language) {
            $localeProperties[$language->locale()] = [
                'type' => 'text',
                'analyzer' => $language->analyzerName(),
            ];
        }

        return [
            'name' => ['type' => 'object', 'properties' => $localeProperties],
            'description' => ['type' => 'object', 'properties' => $localeProperties],
        ];
    }
}
