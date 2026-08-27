<?php

declare(strict_types=1);

namespace App\Images\Support;

use App\Images\Contracts\HasImagePaths;
use Illuminate\Support\Facades\Storage;

class ImageUrlResolver
{
    /**
     * @param  class-string<HasImagePaths>  $paths
     * @return array{webp_url: string, thumbnail_webp_url: string}
     */
    public static function resolve(string $paths, int $id): array
    {
        return [
            'webp_url' => Storage::disk('s3')->url($paths::webp($id)),
            'thumbnail_webp_url' => Storage::disk('s3')->url($paths::thumbnailWebp($id)),
        ];
    }
}
