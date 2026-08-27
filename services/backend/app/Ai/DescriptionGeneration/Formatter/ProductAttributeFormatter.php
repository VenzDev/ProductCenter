<?php

declare(strict_types=1);

namespace App\Ai\DescriptionGeneration\Formatter;

use App\Models\Product;
use App\Product\ObjectValue\ProductAttributeValue;
use App\Product\Support\AttributeDefinitions;

class ProductAttributeFormatter
{
    public function format(Product $product, string $locale): string
    {
        /** @var array<string, mixed> $rawAttributes */
        $rawAttributes = $product->attributes ?? [];

        if ($rawAttributes === []) {
            return 'None specified.';
        }

        $definitions = AttributeDefinitions::all();

        return collect($rawAttributes)
            ->map(fn (mixed $value, string $key) => new ProductAttributeValue($key, $value, $definitions->get($key)))
            ->map(fn (ProductAttributeValue $attribute) => $this->formatAttribute($attribute, $locale))
            ->implode("\n");
    }

    private function formatAttribute(ProductAttributeValue $attribute, string $locale): string
    {
        $valueLabel = $attribute->resolvedValueLabel($locale);

        return "{$attribute->resolvedName($locale)}: ".(is_array($valueLabel) ? implode(', ', $valueLabel) : (string) $valueLabel);
    }
}
