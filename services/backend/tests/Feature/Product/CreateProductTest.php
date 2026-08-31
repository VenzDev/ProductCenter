<?php

declare(strict_types=1);

use App\Models\Category;
use App\Product\Action\CreateProduct;
use App\Product\ObjectValue\NewProduct;
use App\Product\Support\ProductImagePaths;
use App\Storage\StorageDisk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('it persists a product from a NewProduct input', function () {
    Storage::fake(StorageDisk::S3);
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    Storage::disk(StorageDisk::S3)->put('product-images/tmp/upload.jpg', UploadedFile::fake()->image('upload.jpg')->getContent());

    $product = (new CreateProduct)->handle(new NewProduct(
        categoryId: $category->id,
        name: ['en' => 'Widget', 'pl' => 'Gadżet'],
        priceCents: 1999,
        mainImage: 'product-images/tmp/upload.jpg',
        attributes: ['weight_kg' => 1.2],
        description: ['en' => 'A fine widget'],
    ));

    expect($product->exists)->toBeTrue();
    expect($product->getTranslation('name', 'pl', false))->toBe('Gadżet');
    expect($product->getTranslation('description', 'en', false))->toBe('A fine widget');
    expect($product->category_id)->toBe($category->id);
    expect($product->price_cents)->toBe(1999);
    expect($product->currency)->toBe('PLN');
    expect($product->attributes)->toBe(['weight_kg' => 1.2]);
});

test('it runs the image pipeline via the product observers', function () {
    Storage::fake(StorageDisk::S3);
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    Storage::disk(StorageDisk::S3)->put('product-images/tmp/upload.jpg', UploadedFile::fake()->image('upload.jpg')->getContent());

    $product = (new CreateProduct)->handle(new NewProduct(
        categoryId: $category->id,
        name: ['en' => 'Widget'],
        priceCents: 1999,
        mainImage: 'product-images/tmp/upload.jpg',
    ));

    // RelocateUploadedImageJob + GenerateWebpImageJob run synchronously (sync queue in tests).
    expect($product->refresh()->main_image)->toBe("product-images/{$product->id}/main-image.jpg");
    Storage::disk(StorageDisk::S3)->assertExists(ProductImagePaths::webp($product->id));
});
