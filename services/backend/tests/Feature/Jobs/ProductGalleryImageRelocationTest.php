<?php

declare(strict_types=1);

use App\Images\Jobs\RelocateUploadedImageJob;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Product\Support\ProductImageGalleryPaths;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

function createProductImageWithoutPath(): ProductImage
{
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
    ]);

    return ProductImage::create(['product_id' => $product->id, 'path' => 'placeholder']);
}

function stageProductGalleryImage(ProductImage $productImage, string $stagingKey): void
{
    Storage::disk('s3')->put($stagingKey, (string) (new ImageManager(new Driver))->create(20, 20)->toJpeg());
    // saveQuietly avoids the real ProductGalleryObserver dispatch — this test drives the job directly.
    $productImage->path = $stagingKey;
    $productImage->saveQuietly();
}

test('handling the job relocates a staged gallery upload and generates both webp variants', function () {
    Storage::fake('s3');
    $image = createProductImageWithoutPath();
    stageProductGalleryImage($image, 'product-images/gallery/tmp/abc123.jpg');

    // GenerateWebpImageJob::dispatch() inside the job runs synchronously here (sync
    // queue driver in tests), so both variants exist by the time this call returns.
    (new RelocateUploadedImageJob(ProductImage::class, $image->id, 'path', ProductImageGalleryPaths::class))->handle();

    $fresh = $image->fresh();
    expect($fresh->path)->toBe("product-images/gallery/{$image->id}/image.jpg");

    Storage::disk('s3')->assertMissing('product-images/gallery/tmp/abc123.jpg');
    Storage::disk('s3')->assertExists($fresh->path);
    Storage::disk('s3')->assertExists(ProductImageGalleryPaths::webp($image->id));
    Storage::disk('s3')->assertExists(ProductImageGalleryPaths::thumbnailWebp($image->id));
});
