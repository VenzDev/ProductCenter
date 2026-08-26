<?php

declare(strict_types=1);

use App\Images\Jobs\RelocateUploadedImageJob;
use App\Models\Category;
use App\Models\Product;
use App\Product\Support\ProductImagePaths;
use Illuminate\Support\Facades\Bus;

test('creating a product with a main image dispatches the relocation job', function () {
    Bus::fake([RelocateUploadedImageJob::class]);
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
        'main_image' => 'product-images/tmp/abc.jpg',
    ]);

    Bus::assertDispatched(RelocateUploadedImageJob::class, fn ($job) => $job->modelClass === Product::class
        && $job->modelId === $product->id
        && $job->imageColumn === 'main_image'
        && $job->imagePathsClass === ProductImagePaths::class);
});

test('replacing the main image dispatches the relocation job', function () {
    Bus::fake([RelocateUploadedImageJob::class]);
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
        'main_image' => 'product-images/tmp/abc.jpg',
    ]);

    $product->update(['main_image' => 'product-images/tmp/def.jpg']);

    Bus::assertDispatchedTimes(RelocateUploadedImageJob::class, 2);
});

test('saving a product without changing the main image does not dispatch the relocation job', function () {
    Bus::fake([RelocateUploadedImageJob::class]);
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
        'main_image' => 'product-images/tmp/abc.jpg',
    ]);

    $product->update(['price_cents' => 2499]);

    Bus::assertDispatchedTimes(RelocateUploadedImageJob::class, 1);
});
