<?php

declare(strict_types=1);

namespace App\Images\Contracts;

// Implemented by each model's own *ImagePaths helper (e.g. ProductImagePaths,
// BlogPostImagePaths) so GenerateWebpImageJob can stay generic across models
// while each model keeps its own directory/filename naming convention.
interface HasImagePaths
{
    public static function original(int $id, string $extension): string;

    public static function webp(int $id): string;

    public static function thumbnailWebp(int $id): string;
}
