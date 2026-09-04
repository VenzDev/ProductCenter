<?php

declare(strict_types=1);

namespace App\Ai\ImageGeneration\Generator;

use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use RuntimeException;

class PrismProductImageGenerator implements ProductImageGeneratorInterface
{
    private const string IMAGE_MODEL = 'gpt-image-1';

    public function generate(string $prompt): string
    {
        $response = Prism::image()
            ->using(Provider::OpenAI, self::IMAGE_MODEL)
            ->withPrompt($prompt)
            ->withProviderOptions([
                'size' => '1024x1024',
                'quality' => 'high',
                'output_format' => 'png',
            ])
            ->generate();

        $content = $response->firstImage()?->rawContent();

        if (! $content) {
            throw new RuntimeException('Prism image generation returned no image.');
        }

        return $content;
    }
}
