<?php

declare(strict_types=1);

namespace App\Ai\DescriptionGeneration\Formatter;

use App\Models\Product;
use App\Product\ObjectValue\ProductAttributeValue;

class ProductAttributeFormatter
{
    public function format(Product $product, string $locale): string
    {
        $attributes = $product->getAttributeCollection()->get();

        if ($attributes->isEmpty()) {
            return 'None specified.';
        }

        return $attributes
            ->map(fn (ProductAttributeValue $attribute) => $this->formatAttribute($attribute, $locale))
            ->implode("\n");
    }

    private function formatAttribute(ProductAttributeValue $attribute, string $locale): string
    {
        $valueLabel = $attribute->resolvedValueLabel($locale);

        return "{$attribute->resolvedName($locale)}: ".(is_array($valueLabel) ? implode(', ', $valueLabel) : (string) $valueLabel);
    }
}
