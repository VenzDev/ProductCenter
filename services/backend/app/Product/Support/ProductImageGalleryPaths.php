<?php

declare(strict_types=1);

namespace App\Product\Support;

use App\Images\Contracts\HasImagePaths;
use App\Images\Support\WebpImageNaming;

class ProductImageGalleryPaths implements HasImagePaths
{
    public static function original(int $productImageId, string $extension): string
    {
        return self::stem($productImageId).".{$extension}";
    }

    public static function webp(int $productImageId): string
    {
        return WebpImageNaming::webp(self::stem($productImageId));
    }

    public static function thumbnailWebp(int $productImageId): string
    {
        return WebpImageNaming::thumbnailWebp(self::stem($productImageId));
    }

    private static function stem(int $productImageId): string
    {
        return "product-images/gallery/{$productImageId}/image";
    }
}
