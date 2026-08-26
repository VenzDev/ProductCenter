<?php

declare(strict_types=1);

namespace App\Product\Observers;

use App\Images\Jobs\RelocateUploadedImageJob;
use App\Models\Product;
use App\Product\Support\ProductImagePaths;

class ProductObserver
{
    public function saved(Product $product): void
    {
        $this->dispatchWebpGenerationIfNeeded($product);
    }

    private function dispatchWebpGenerationIfNeeded(Product $product): void
    {
        // wasChanged() is unreliable here: Eloquent's performInsert() never populates the
        // $changes array, so it's always empty right after a create. syncOriginal() only
        // runs *after* this 'saved' event fires (on both create and update), so comparing
        // against getOriginal() directly catches "added" and "changed" uniformly.
        if ($product->main_image && $product->main_image !== $product->getOriginal('main_image')) {
            RelocateUploadedImageJob::dispatch(Product::class, $product->id, 'main_image', ProductImagePaths::class);
        }
    }
}
