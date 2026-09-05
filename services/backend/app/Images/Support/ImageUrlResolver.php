<?php

declare(strict_types=1);

namespace App\Images\Support;

use App\Models\Asset;
use App\Storage\StorageDisk;
use Illuminate\Support\Facades\Storage;

class ImageUrlResolver
{
    /**
     * @return array{webp_url: string, thumbnail_webp_url: string}|null
     */
    public static function resolve(?Asset $asset): ?array
    {
        if (! $asset) {
            return null;
        }

        return [
            'webp_url' => Storage::disk(StorageDisk::S3)->url(AssetPathResolver::webp($asset)),
            'thumbnail_webp_url' => Storage::disk(StorageDisk::S3)->url(AssetPathResolver::thumbnailWebp($asset)),
        ];
    }
}
