<?php

declare(strict_types=1);

namespace App\Product\Search\Language;

/**
 * English is one of OpenSearch's built-in language analyzers, but the built-in "english"
 * analyzer's filter chain is fixed and can't have a synonym filter inserted into it — so
 * it's reimplemented here as a custom analyzer instead, per OpenSearch's own docs on
 * customizing a built-in language analyzer.
 */
final class EnglishSearchLanguage implements SearchLanguage
{
    public function locale(): string
    {
        return 'en';
    }

    public function analyzerName(): string
    {
        return 'en_analyzer';
    }

    public function filters(): array
    {
        return [
            'en_synonyms' => [
                'type' => 'synonym',
                'synonyms_path' => 'analysis/synonyms_en.txt',
            ],
            'en_stop' => [
                'type' => 'stop',
                'stopwords' => '_english_',
            ],
            'en_stemmer' => [
                'type' => 'stemmer',
                'language' => 'english',
            ],
            'en_possessive_stemmer' => [
                'type' => 'stemmer',
                'language' => 'possessive_english',
            ],
        ];
    }

    public function analyzer(): array
    {
        return [
            'type' => 'custom',
            'tokenizer' => 'standard',
            'filter' => ['en_possessive_stemmer', 'lowercase', 'en_synonyms', 'en_stop', 'en_stemmer'],
        ];
    }
}
