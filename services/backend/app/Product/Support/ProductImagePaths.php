<?php

declare(strict_types=1);

namespace App\Product\Support;

use App\Images\Contracts\HasImagePaths;
use App\Images\Support\WebpImageNaming;

class ProductImagePaths implements HasImagePaths
{
    public static function original(int $productId, string $extension): string
    {
        return self::stem($productId).".{$extension}";
    }

    public static function webp(int $productId): string
    {
        return WebpImageNaming::webp(self::stem($productId));
    }

    public static function thumbnailWebp(int $productId): string
    {
        return WebpImageNaming::thumbnailWebp(self::stem($productId));
    }

    private static function stem(int $productId): string
    {
        return "product-images/{$productId}/main-image";
    }
}
