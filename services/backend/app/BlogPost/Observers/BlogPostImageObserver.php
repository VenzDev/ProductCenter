<?php

declare(strict_types=1);

namespace App\BlogPost\Observers;

use App\BlogPost\Support\BlogPostImagePaths;
use App\Images\Observers\WebpImageObserver;

class BlogPostImageObserver extends WebpImageObserver
{
    protected function imageColumn(): string
    {
        return 'preview_image';
    }

    protected function imagePathsClass(): string
    {
        return BlogPostImagePaths::class;
    }
}
