<?php

declare(strict_types=1);

namespace App\Images\Support;

use App\Images\Enums\AssetRole;
use App\Models\Asset;

// Single place that knows the storage path convention for every asset role, replacing what
// used to be a separate *ImagePaths class per owning model.
class AssetPathResolver
{
    public static function original(Asset $asset, string $extension): string
    {
        return self::stem($asset).".{$extension}";
    }

    public static function webp(Asset $asset): string
    {
        return WebpImageNaming::webp(self::stem($asset));
    }

    public static function thumbnailWebp(Asset $asset): string
    {
        return WebpImageNaming::thumbnailWebp(self::stem($asset));
    }

    private static function stem(Asset $asset): string
    {
        return match ($asset->role) {
            AssetRole::MainImage => "product-images/{$asset->owner_id}/main-image",
            AssetRole::PreviewImage => "blog-post-images/{$asset->owner_id}/preview-image",
            AssetRole::GalleryImage => "product-images/gallery/{$asset->id}/image",
        };
    }
}
