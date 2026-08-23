<?php

use App\Models\Category;
use App\Models\Product;
use App\Product\Jobs\GenerateProductWebpImageJob;
use Illuminate\Support\Facades\Bus;

test('creating a product with a main image dispatches the webp job', function () {
    Bus::fake([GenerateProductWebpImageJob::class]);
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
        'main_image' => 'product-images/tmp/abc.jpg',
    ]);

    Bus::assertDispatched(GenerateProductWebpImageJob::class, fn ($job) => $job->productId === $product->id);
});

test('replacing the main image dispatches the webp job', function () {
    Bus::fake([GenerateProductWebpImageJob::class]);
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
        'main_image' => 'product-images/tmp/abc.jpg',
    ]);

    $product->update(['main_image' => 'product-images/tmp/def.jpg']);

    Bus::assertDispatchedTimes(GenerateProductWebpImageJob::class, 2);
});

test('saving a product without changing the main image does not dispatch the webp job', function () {
    Bus::fake([GenerateProductWebpImageJob::class]);
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
        'main_image' => 'product-images/tmp/abc.jpg',
    ]);

    $product->update(['price_cents' => 2499]);

    Bus::assertDispatchedTimes(GenerateProductWebpImageJob::class, 1);
});
