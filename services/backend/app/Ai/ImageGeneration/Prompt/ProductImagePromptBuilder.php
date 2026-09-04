<?php

declare(strict_types=1);

namespace App\Ai\ImageGeneration\Prompt;

use App\Ai\DescriptionGeneration\Formatter\ProductAttributeFormatter;
use App\Models\Product;

class ProductImagePromptBuilder
{
    private const string STYLE_SUFFIX = 'Style: clean e-commerce product photo, plain white background, studio lighting, no text or watermarks.';

    public function __construct(
        private readonly ProductAttributeFormatter $attributeFormatter,
    ) {}

    public function build(Product $product): string
    {
        $locale = config('app.fallback_locale');

        $parts = ["Product name: {$product->getTranslation('name', $locale)}"];

        $description = $product->getTranslation('description', $locale, false);
        if ($description) {
            $parts[] = "Description: {$description}";
        }

        $parts[] = "Attributes:\n{$this->attributeFormatter->format($product, $locale)}";
        $parts[] = self::STYLE_SUFFIX;

        return implode("\n\n", $parts);
    }
}
