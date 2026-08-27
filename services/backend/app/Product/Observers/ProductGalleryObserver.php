<?php

declare(strict_types=1);

namespace App\Product\Observers;

use App\Images\Observers\WebpImageObserver;
use App\Product\Support\ProductImageGalleryPaths;

class ProductGalleryObserver extends WebpImageObserver
{
    protected function imageColumn(): string
    {
        return 'path';
    }

    protected function imagePathsClass(): string
    {
        return ProductImageGalleryPaths::class;
    }
}
