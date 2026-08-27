<?php

declare(strict_types=1);

namespace App\Ai\ProductDescription;

use Prism\Prism\ValueObjects\Media\Image;

interface ProductDescriptionGeneratorInterface
{
    public function generate(string $productName, string $attributesText, ?Image $image, string $locale): string;
}
