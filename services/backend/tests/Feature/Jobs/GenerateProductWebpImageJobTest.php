<?php

use App\Models\Category;
use App\Models\Product;
use App\Product\Jobs\GenerateProductWebpImageJob;
use App\Product\Support\ProductImagePaths;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

function fakeJpegBytes(): string
{
    return (string) (new ImageManager(new Driver))->create(20, 20)->toJpeg();
}

function createProductWithoutImage(): Product
{
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

    return Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
    ]);
}

function stageImage(Product $product, string $stagingKey): void
{
    Storage::disk('s3')->put($stagingKey, fakeJpegBytes());
    // saveQuietly avoids the real ProductObserver dispatch — these tests drive the job directly.
    $product->main_image = $stagingKey;
    $product->saveQuietly();
}

test('handling the job relocates a staged upload and generates both webp variants', function () {
    Storage::fake('s3');
    $product = createProductWithoutImage();
    stageImage($product, 'product-images/tmp/abc123.jpg');

    (new GenerateProductWebpImageJob($product->id))->handle();

    $fresh = $product->fresh();
    expect($fresh->main_image)->toBe("product-images/{$product->id}/main-image.jpg");

    Storage::disk('s3')->assertMissing('product-images/tmp/abc123.jpg');
    Storage::disk('s3')->assertExists($fresh->main_image);
    Storage::disk('s3')->assertExists(ProductImagePaths::webp($product->id));
    Storage::disk('s3')->assertExists(ProductImagePaths::thumbnailWebp($product->id));
});

test('replacing the image removes the stale canonical original when the extension changes', function () {
    Storage::fake('s3');
    $product = createProductWithoutImage();
    stageImage($product, "product-images/{$product->id}/uploads/first.jpg");
    (new GenerateProductWebpImageJob($product->id))->handle();

    stageImage($product->fresh(), "product-images/{$product->id}/uploads/second.png");

    (new GenerateProductWebpImageJob($product->id))->handle();

    $fresh = $product->fresh();
    expect($fresh->main_image)->toBe("product-images/{$product->id}/main-image.png");
    Storage::disk('s3')->assertMissing("product-images/{$product->id}/main-image.jpg");
    Storage::disk('s3')->assertExists("product-images/{$product->id}/main-image.png");
});

test('a job for a product that no longer exists does nothing without throwing', function () {
    expect(fn () => (new GenerateProductWebpImageJob(999999))->handle())
        ->not->toThrow(Throwable::class);
});
