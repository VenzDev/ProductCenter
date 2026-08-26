<?php

declare(strict_types=1);

namespace App\BlogPost\Observers;

use App\BlogPost\Support\BlogPostImagePaths;
use App\Images\Jobs\RelocateUploadedImageJob;
use App\Models\BlogPost;

class BlogPostObserver
{
    public function saved(BlogPost $blogPost): void
    {
        $this->dispatchWebpGenerationIfNeeded($blogPost);
    }

    private function dispatchWebpGenerationIfNeeded(BlogPost $blogPost): void
    {
        if ($blogPost->preview_image && $blogPost->preview_image !== $blogPost->getOriginal('preview_image')) {
            RelocateUploadedImageJob::dispatch(BlogPost::class, $blogPost->id, 'preview_image', BlogPostImagePaths::class);
        }
    }
}
