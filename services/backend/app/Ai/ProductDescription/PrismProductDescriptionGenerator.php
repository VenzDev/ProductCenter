<?php

declare(strict_types=1);

namespace App\Ai\ProductDescription;

use App\Enums\Language;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\ValueObjects\Media\Image;

class PrismProductDescriptionGenerator implements ProductDescriptionGeneratorInterface
{
    private const string TEXT_MODEL = 'gpt-4o-mini';

    public function generate(string $productName, string $attributesText, ?Image $image, string $locale): string
    {
        $language = Language::tryFrom($locale)?->label() ?? $locale;

        $prompt = "Product name: {$productName}\n\nAttributes:\n{$attributesText}".
            ($image !== null ? "\n\nUse the attached product image to inform the description as well." : '');

        $response = Prism::text()
            ->using(Provider::OpenAI, self::TEXT_MODEL)
            ->withSystemPrompt(
                "Write a compelling, factual e-commerce product description in {$language}, ".
                'based on the given product name, attributes, and (if provided) product image. '.
                'Plain flowing prose, no markdown, no headings, no bullet lists, 2-4 sentences.'
            )
            ->withPrompt($prompt, $image !== null ? [$image] : [])
            ->asText();

        return trim($response->text);
    }
}
