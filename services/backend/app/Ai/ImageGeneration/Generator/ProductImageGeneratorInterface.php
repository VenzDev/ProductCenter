<?php

declare(strict_types=1);

namespace App\Ai\ImageGeneration\Generator;

interface ProductImageGeneratorInterface
{
    /**
     * @return string raw image bytes
     */
    public function generate(string $prompt): string;
}
