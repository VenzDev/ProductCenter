<?php

declare(strict_types=1);

namespace App\Product\Search\Index;

use App\Product\Search\Language\SupportedSearchLanguages;
use OpenSearch\Client;

/**
 * Creates the "products" OpenSearch index with its per-language mapping and analyzers,
 * and keeps its field mapping in sync afterwards. Kept separate from ProductSearchIndexer
 * (which only ever pushes/removes documents) so indexing a single product never carries
 * the cost of an index-existence check — index provisioning/mapping sync is a deploy-time
 * step, run via the search:install-index command.
 */
class ProductSearchIndexManager
{
    private const string INDEX = 'products';

    public function __construct(
        private readonly Client $client,
        private readonly SupportedSearchLanguages $languages,
    ) {}

    /**
     * Creates the index — settings and mapping together in one call, so a custom analyzer
     * is never referenced before OpenSearch has registered it — if it doesn't exist yet.
     * Otherwise, applies the mapping (only) to the existing index: analysis settings can't
     * change after creation without closing the index, but new mapping fields — e.g. adding
     * category_id below — can be added to an already-populated index without a rebuild.
     */
    public function ensureIndexExists(): void
    {
        $mappings = [
            'dynamic_templates' => $this->buildAttributeDynamicTemplates(),
            'properties' => $this->buildProperties(),
        ];

        if ($this->client->indices()->exists(['index' => self::INDEX])) {
            $this->client->indices()->putMapping(['index' => self::INDEX, 'body' => $mappings]);

            return;
        }

        $this->client->indices()->create([
            'index' => self::INDEX,
            'body' => [
                'settings' => ['analysis' => $this->buildAnalysisSettings()],
                'mappings' => $mappings,
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
            'category_id' => ['type' => 'keyword'],
            'price_cents' => ['type' => 'long'],
            // Left dynamic on purpose: which attributes exist is decided by category/admin
            // config (see Attribute::$filterable), not the search index schema — new
            // filterable attributes must not require a mapping change. See the dynamic
            // templates below for how each key's type is inferred on first sight.
            'attributes' => ['type' => 'object'],
        ];
    }

    /**
     * ProductSearchIndexer only ever writes filterable attribute values here (number,
     * select, or multiselect) — text/text_translatable attributes are never filterable
     * (see Attribute::$filterable), so no key ever holds a nested per-locale object,
     * keeping every attribute key's value type consistent across all products.
     *
     * @return list<array<string, array<string, mixed>>>
     */
    private function buildAttributeDynamicTemplates(): array
    {
        return [
            ['attributes_integer' => [
                'path_match' => 'attributes.*',
                'match_mapping_type' => 'long',
                'mapping' => ['type' => 'double'],
            ]],
            ['attributes_float' => [
                'path_match' => 'attributes.*',
                'match_mapping_type' => 'double',
                'mapping' => ['type' => 'double'],
            ]],
            ['attributes_string' => [
                'path_match' => 'attributes.*',
                'match_mapping_type' => 'string',
                'mapping' => ['type' => 'keyword'],
            ]],
        ];
    }
}
