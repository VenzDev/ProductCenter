<?php

declare(strict_types=1);

namespace App\BlogPost\Support;

use App\Images\Contracts\HasImagePaths;

class BlogPostImagePaths implements HasImagePaths
{
    public static function original(int $blogPostId, string $extension): string
    {
        return "blog-post-images/{$blogPostId}/preview-image.{$extension}";
    }

    public static function webp(int $blogPostId): string
    {
        return "blog-post-images/{$blogPostId}/preview-image.webp";
    }

    public static function thumbnailWebp(int $blogPostId): string
    {
        return "blog-post-images/{$blogPostId}/preview-image-thumbnail.webp";
    }
}
