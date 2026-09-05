<?php

declare(strict_types=1);

use App\Images\Jobs\RelocateUploadedAssetJob;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Bus;
use Tests\Factories\ProductFactory;

test('creating a main image asset dispatches the relocation job', function () {
    Bus::fake([RelocateUploadedAssetJob::class]);
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
    ]);

    $asset = $product->mainImage()->create(['path' => 'product-images/tmp/abc.jpg']);

    Bus::assertDispatched(RelocateUploadedAssetJob::class, fn ($job) => $job->assetId === $asset->id);
});

test('replacing an asset path dispatches the relocation job again', function () {
    Bus::fake([RelocateUploadedAssetJob::class]);
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
    ]);
    $asset = $product->mainImage()->create(['path' => 'product-images/tmp/abc.jpg']);

    $asset->update(['path' => 'product-images/tmp/def.jpg']);

    Bus::assertDispatchedTimes(RelocateUploadedAssetJob::class, 2);
});

test('updating an asset without changing its path does not dispatch the relocation job', function () {
    Bus::fake([RelocateUploadedAssetJob::class]);
    $product = ProductFactory::new()->createQuietly();
    $asset = $product->galleryImages()->create(['path' => 'product-images/gallery/tmp/abc.jpg']);
    Bus::fake([RelocateUploadedAssetJob::class]);

    $asset->update(['order' => 5]);

    Bus::assertDispatchedTimes(RelocateUploadedAssetJob::class, 0);
});

test('creating a gallery image asset dispatches the relocation job', function () {
    Bus::fake([RelocateUploadedAssetJob::class]);
    $product = ProductFactory::new()->createQuietly();

    $asset = $product->galleryImages()->create(['path' => 'product-images/gallery/tmp/abc.jpg']);

    Bus::assertDispatched(RelocateUploadedAssetJob::class, fn ($job) => $job->assetId === $asset->id);
});
