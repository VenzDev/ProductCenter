<?php

declare(strict_types=1);

use App\Images\Jobs\RelocateUploadedImageJob;
use App\Models\Product;
use App\Models\ProductImage;
use App\Product\Support\ProductImageGalleryPaths;
use Illuminate\Support\Facades\Bus;
use Tests\Factories\ProductFactory;

function createProductForGallery(): Product
{
    return ProductFactory::new()->createQuietly();
}

test('creating a product image dispatches the relocation job', function () {
    Bus::fake([RelocateUploadedImageJob::class]);
    $product = createProductForGallery();

    $image = ProductImage::create([
        'product_id' => $product->id,
        'path' => 'product-images/gallery/tmp/abc.jpg',
    ]);

    Bus::assertDispatched(RelocateUploadedImageJob::class, fn ($job) => $job->modelClass === ProductImage::class
        && $job->modelId === $image->id
        && $job->imageColumn === 'path'
        && $job->imagePathsClass === ProductImageGalleryPaths::class);
});

test('reordering a product image does not dispatch the relocation job', function () {
    Bus::fake([RelocateUploadedImageJob::class]);
    $product = createProductForGallery();
    $image = ProductImage::create([
        'product_id' => $product->id,
        'path' => 'product-images/gallery/tmp/abc.jpg',
    ]);

    $image->update(['order' => 5]);

    Bus::assertDispatchedTimes(RelocateUploadedImageJob::class, 1);
});
