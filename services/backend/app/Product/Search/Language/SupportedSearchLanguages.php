<?php

declare(strict_types=1);

namespace App\Product\Search\Language;

/**
 * The single place that declares which languages the product search index supports.
 * ProductSearchIndexManager and ProductSearcher only ever depend on this list, never on
 * a concrete SearchLanguage — extending to another language means adding a class here.
 */
final class SupportedSearchLanguages
{
    /**
     * @return list<SearchLanguage>
     */
    public function all(): array
    {
        return [
            new PolishSearchLanguage,
            new EnglishSearchLanguage,
        ];
    }
}
