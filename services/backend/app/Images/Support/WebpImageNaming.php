<?php

declare(strict_types=1);

namespace App\Images\Support;

// Single place that knows the "same stem, plus a fixed suffix" webp/thumbnail
// naming convention — shared by every *ImagePaths class (computing the expected
// static path) and by GenerateWebpImageJob (deriving the same names at runtime
// from wherever the source image actually landed).
class WebpImageNaming
{
    public static function webp(string $stem): string
    {
        return "{$stem}.webp";
    }

    public static function thumbnailWebp(string $stem): string
    {
        return "{$stem}-thumbnail.webp";
    }
}
