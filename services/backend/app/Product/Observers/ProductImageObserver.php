<?php

declare(strict_types=1);

namespace App\Product\Observers;

use App\Images\Observers\WebpImageObserver;
use App\Product\Support\ProductImagePaths;

class ProductImageObserver extends WebpImageObserver
{
    protected function imageColumn(): string
    {
        return 'main_image';
    }

    protected function imagePathsClass(): string
    {
        return ProductImagePaths::class;
    }
}
