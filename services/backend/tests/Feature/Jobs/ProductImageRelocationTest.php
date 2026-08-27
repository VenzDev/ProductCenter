<?php

declare(strict_types=1);

use App\Images\Jobs\RelocateUploadedImageJob;
use App\Models\Category;
use App\Models\Product;
use App\Product\Support\ProductImagePaths;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

function fakeJpegBytes(): string
{
    return (string) (new ImageManager(new Driver))->create(20, 20)->toJpeg();
}

function createProductWithStagedImage(string $stagingKey): Product
{
    Storage::disk('s3')->put($stagingKey, fakeJpegBytes());
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

    // withoutEvents skips the real ProductImageObserver dispatch — these tests drive
    // RelocateUploadedImageJob directly instead.
    return Product::withoutEvents(fn () => Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
        'main_image' => $stagingKey,
    ]));
}

function restageProductImage(Product $product, string $stagingKey): void
{
    Storage::disk('s3')->put($stagingKey, fakeJpegBytes());
    // saveQuietly avoids the real ProductImageObserver dispatch — these tests drive the job directly.
    $product->main_image = $stagingKey;
    $product->saveQuietly();
}

function relocateProductImage(Product $product): void
{
    // GenerateWebpImageJob::dispatch() inside the job runs synchronously here (sync
    // queue driver in tests), so both variants exist by the time this call returns.
    (new RelocateUploadedImageJob(Product::class, $product->id, 'main_image', ProductImagePaths::class))->handle();
}

test('handling the job relocates a staged upload and generates both webp variants', function () {
    Storage::fake('s3');
    $product = createProductWithStagedImage('product-images/tmp/abc123.jpg');

    relocateProductImage($product);

    $fresh = $product->fresh();
    expect($fresh->main_image)->toBe("product-images/{$product->id}/main-image.jpg");

    Storage::disk('s3')->assertMissing('product-images/tmp/abc123.jpg');
    Storage::disk('s3')->assertExists($fresh->main_image);
    Storage::disk('s3')->assertExists(ProductImagePaths::webp($product->id));
    Storage::disk('s3')->assertExists(ProductImagePaths::thumbnailWebp($product->id));
});

test('replacing the image removes the stale canonical original when the extension changes', function () {
    Storage::fake('s3');
    $product = createProductWithStagedImage('product-images/tmp/first.jpg');
    relocateProductImage($product);

    restageProductImage($product->fresh(), "product-images/{$product->id}/uploads/second.png");

    relocateProductImage($product->fresh());

    $fresh = $product->fresh();
    expect($fresh->main_image)->toBe("product-images/{$product->id}/main-image.png");
    Storage::disk('s3')->assertMissing("product-images/{$product->id}/main-image.jpg");
    Storage::disk('s3')->assertExists("product-images/{$product->id}/main-image.png");
});

test('a job for a product that no longer exists does nothing without throwing', function () {
    expect(fn () => (new RelocateUploadedImageJob(Product::class, 999999, 'main_image', ProductImagePaths::class))->handle())
        ->not->toThrow(Throwable::class);
});
