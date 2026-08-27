<?php

declare(strict_types=1);

namespace App\Ai\DescriptionGeneration\Formatter;

use App\Models\Attribute;
use App\Models\Product;
use App\Product\ObjectValue\ProductAttributeValue;
use Illuminate\Support\Collection;

class ProductAttributeFormatter
{
    public function format(Product $product, string $locale): string
    {
        /** @var array<string, mixed> $rawAttributes */
        $rawAttributes = $product->attributes ?? [];

        if ($rawAttributes === []) {
            return 'None specified.';
        }

        $definitions = $this->getDefinitions($rawAttributes);

        return collect($rawAttributes)
            ->map(fn (mixed $value, string $key) => new ProductAttributeValue($key, $value, $definitions->get($key)))
            ->map(fn (ProductAttributeValue $attribute) => $this->formatAttribute($attribute, $locale))
            ->implode("\n");
    }

    /**
     * @param  array<string, mixed>  $rawAttributes
     * @return Collection<string, Attribute>
     */
    private function getDefinitions(mixed $rawAttributes): Collection
    {
        return Attribute::query()->whereIn('key', array_keys($rawAttributes))->get()->keyBy('key');
    }

    private function formatAttribute(ProductAttributeValue $attribute, string $locale): string
    {
        $valueLabel = $attribute->resolvedValueLabel($locale);

        return "{$attribute->resolvedName($locale)}: ".(is_array($valueLabel) ? implode(', ', $valueLabel) : (string) $valueLabel);
    }
}
