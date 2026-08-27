<?php

declare(strict_types=1);

namespace App\Product\Search\Language;

/**
 * Requires the analysis-stempel plugin (see services/backend/docker/opensearch/Dockerfile)
 * for the "polish_stop"/"polish_stem" filters — Polish has no built-in OpenSearch analyzer.
 */
final class PolishSearchLanguage implements SearchLanguage
{
    public function locale(): string
    {
        return 'pl';
    }

    public function analyzerName(): string
    {
        return 'pl_analyzer';
    }

    public function filters(): array
    {
        return [
            'pl_synonyms' => [
                'type' => 'synonym',
                'synonyms_path' => 'analysis/synonyms_pl.txt',
            ],
        ];
    }

    public function analyzer(): array
    {
        return [
            'type' => 'custom',
            'tokenizer' => 'standard',
            // polish_stop/polish_stem are fixed, non-configurable filters that ship with
            // analysis-stempel — referenced by name, unlike the synonym filter above.
            'filter' => ['lowercase', 'pl_synonyms', 'polish_stop', 'polish_stem'],
        ];
    }
}
