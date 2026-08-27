<?php

declare(strict_types=1);

namespace App\BlogPost\Support;

use App\Images\Contracts\HasImagePaths;
use App\Images\Support\WebpImageNaming;

class BlogPostImagePaths implements HasImagePaths
{
    public static function original(int $blogPostId, string $extension): string
    {
        return self::stem($blogPostId).".{$extension}";
    }

    public static function webp(int $blogPostId): string
    {
        return WebpImageNaming::webp(self::stem($blogPostId));
    }

    public static function thumbnailWebp(int $blogPostId): string
    {
        return WebpImageNaming::thumbnailWebp(self::stem($blogPostId));
    }

    private static function stem(int $blogPostId): string
    {
        return "blog-post-images/{$blogPostId}/preview-image";
    }
}
