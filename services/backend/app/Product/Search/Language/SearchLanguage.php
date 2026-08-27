<?php

declare(strict_types=1);

namespace App\Product\Search\Language;

/**
 * One implementation per supported search language. Adding a new language means adding
 * a new class here and registering it in SupportedSearchLanguages — nothing else in the
 * search stack (index mapping, indexer, searcher) needs to change.
 */
interface SearchLanguage
{
    /**
     * Matches the spatie/laravel-translatable locale key used on Product, e.g. "pl".
     */
    public function locale(): string;

    public function analyzerName(): string;

    /**
     * Named custom token filters this language's analyzer depends on, merged with every
     * other language's filters into the index's top-level "analysis.filter" settings.
     *
     * @return array<string, array<string, mixed>>
     */
    public function filters(): array;

    /**
     * The analyzer definition itself, placed under "analysis.analyzer.{analyzerName()}".
     *
     * @return array<string, mixed>
     */
    public function analyzer(): array;
}
