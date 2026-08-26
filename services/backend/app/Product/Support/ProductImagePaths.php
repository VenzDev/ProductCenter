<?php

declare(strict_types=1);

namespace App\Product\Support;

use App\Images\Contracts\HasImagePaths;

class ProductImagePaths implements HasImagePaths
{
    public static function original(int $productId, string $extension): string
    {
        return "product-images/{$productId}/main-image.{$extension}";
    }

    public static function webp(int $productId): string
    {
        return "product-images/{$productId}/main-image.webp";
    }

    public static function thumbnailWebp(int $productId): string
    {
        return "product-images/{$productId}/main-image-thumbnail.webp";
    }
}
