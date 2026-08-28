<?php

declare(strict_types=1);

namespace App\Product\Collection;

use App\Product\ObjectValue\ProductAttributeValue;
use App\Product\Support\AttributeDefinitions;
use Illuminate\Support\Collection;

/**
 * Wraps a product's raw `attributes` JSONB map (attribute key => raw value). Centralizes
 * two things the search indexer and the API resource both need: narrowing down to
 * filterable attributes only, and resolving each raw value against its Attribute
 * definition — previously duplicated independently in both places.
 */
class ProductAttributeCollection
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(private readonly array $attributes) {}

    /**
     * Only attributes flagged filterable — the raw JSONB may hold non-filterable ones
     * too (e.g. free text), which the search index must never carry (see
     * ProductSearchIndexManager's dynamic templates, which assume every indexed
     * attribute value is scalar or array-of-scalar, never a text_translatable's
     * per-locale object).
     */
    public function filterable(): self
    {
        $definitions = AttributeDefinitions::all();

        $filtered = collect($this->attributes)
            ->filter(fn (mixed $value, string $key) => $definitions->get($key)?->filterable === true)
            ->all();

        return new self($filtered);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRaw(): array
    {
        return $this->attributes;
    }

    /**
     * @return Collection<int, ProductAttributeValue>
     */
    public function get(): Collection
    {
        $definitions = AttributeDefinitions::all();

        return collect($this->attributes)
            ->map(fn (mixed $value, string $key) => new ProductAttributeValue($key, $value, $definitions->get($key)))
            ->values();
    }
}
