<?php

declare(strict_types=1);

use App\Mcp\Servers\ProductCenterServer;
use App\Mcp\Tools\CreateProductTool;
use App\Models\Category;
use App\Models\Product;
use App\Product\Support\ProductImagePaths;
use App\Storage\StorageDisk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

test('it creates a product from the tool arguments', function () {
    Storage::fake(StorageDisk::S3);
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $bytes = UploadedFile::fake()->image('product.jpg')->getContent();
    Http::fake(['cdn.example.com/*' => Http::response($bytes, 200, ['Content-Type' => 'image/jpeg'])]);

    $response = ProductCenterServer::tool(CreateProductTool::class, [
        'category_id' => $category->id,
        'name' => 'Wireless Mouse',
        'price_cents' => 4999,
        'image_url' => 'https://cdn.example.com/mouse.jpg',
        'description' => 'A comfortable everyday mouse.',
    ]);

    $response->assertOk()->assertSee('Created product');

    $product = Product::firstWhere('category_id', $category->id);
    expect($product)->not->toBeNull();
    expect($product->getTranslation('name', 'en', false))->toBe('Wireless Mouse');
    expect($product->price_cents)->toBe(4999);
    expect($product->currency)->toBe('PLN');
    expect($product->getTranslation('description', 'en', false))->toBe('A comfortable everyday mouse.');

    // The main image was staged and the observer pipeline ran (sync queue in tests).
    expect($product->refresh()->main_image)->toBe("product-images/{$product->id}/main-image.jpg");
    Storage::disk(StorageDisk::S3)->assertExists(ProductImagePaths::webp($product->id));
});

test('it reports an error when the category does not exist', function () {
    Http::fake();

    $response = ProductCenterServer::tool(CreateProductTool::class, [
        'category_id' => 999,
        'name' => 'Ghost',
        'price_cents' => 100,
        'image_url' => 'https://cdn.example.com/x.jpg',
    ]);

    $response->assertHasErrors();
    expect(Product::count())->toBe(0);
});

test('it reports an error when image_url is not an image', function () {
    Storage::fake(StorageDisk::S3);
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    Http::fake(['*' => Http::response('<html>nope</html>', 200, ['Content-Type' => 'text/html'])]);

    $response = ProductCenterServer::tool(CreateProductTool::class, [
        'category_id' => $category->id,
        'name' => 'Broken',
        'price_cents' => 100,
        'image_url' => 'https://cdn.example.com/x.html',
    ]);

    $response->assertHasErrors();
    expect(Product::count())->toBe(0);
});
