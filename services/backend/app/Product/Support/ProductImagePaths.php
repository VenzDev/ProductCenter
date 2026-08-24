<?php

declare(strict_types=1);

namespace App\Product\Support;

class ProductImagePaths
{
    public static function directory(int $productId): string
    {
        return "product-images/{$productId}";
    }

    public static function original(int $productId, string $extension): string
    {
        return self::directory($productId)."/main-image.{$extension}";
    }

    public static function webp(int $productId): string
    {
        return self::directory($productId).'/main-image.webp';
    }

    public static function thumbnailWebp(int $productId): string
    {
        return self::directory($productId).'/main-image-thumbnail.webp';
    }
}
