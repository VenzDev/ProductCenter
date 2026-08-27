<?php

declare(strict_types=1);

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;

function createProduct(): Product
{
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

    return Product::create([
        'category_id' => $category->id,
        'name' => 'Washing machine',
        'description' => 'Front-loading washing machine.',
        'price_cents' => 199900,
        'currency' => 'PLN',
        'attributes' => ['weight_kg' => 70],
    ]);
}

test('products can be listed', function () {
    $product = createProduct();

    $response = $this->getJson('/api/v1/products');

    $response->assertOk();
    $response->assertJsonPath('data.0.id', $product->id);
    $response->assertJsonPath('data.0.name', 'Washing machine');
});

test('the latest 4 products are returned, most recent first', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $products = collect(range(1, 5))->map(fn (int $i) => Product::create([
        'category_id' => $category->id,
        'name' => "Product {$i}",
        'price_cents' => 1000,
        'currency' => 'PLN',
    ]));

    $response = $this->getJson('/api/v1/products/latest');

    $response->assertOk();
    $response->assertJsonCount(4, 'data');
    $response->assertJsonPath('data.0.id', $products->last()->id);
});

test('a single product can be retrieved', function () {
    $product = createProduct();

    $response = $this->getJson("/api/v1/products/{$product->id}");

    $response->assertOk();
    $response->assertJson([
        'data' => [
            'id' => $product->id,
            'category' => ['id' => $product->category_id, 'name' => 'Electronics'],
            'name' => 'Washing machine',
            'description' => 'Front-loading washing machine.',
            'price_cents' => 199900,
            'currency' => 'PLN',
            'attributes' => [
                ['key' => 'weight_kg', 'name' => 'weight_kg', 'value' => 70, 'value_label' => 70],
            ],
        ],
    ]);
});

test('a product with a main image exposes the webp and thumbnail URLs', function () {
    $product = createProduct();
    $product->main_image = "product-images/{$product->id}/main-image.jpg";
    $product->saveQuietly();

    $response = $this->getJson("/api/v1/products/{$product->id}");

    $response->assertOk();
    $response->assertJsonPath('data.main_image.webp_url', fn ($url) => str_ends_with($url, "product-images/{$product->id}/main-image.webp"));
    $response->assertJsonPath('data.main_image.thumbnail_webp_url', fn ($url) => str_ends_with($url, "product-images/{$product->id}/main-image-thumbnail.webp"));
});

test('a product without a main image has a null main_image', function () {
    $product = createProduct();

    $response = $this->getJson("/api/v1/products/{$product->id}");

    $response->assertOk();
    $response->assertJsonPath('data.main_image', null);
});

function createGalleryImageQuietly(Product $product, string $path, int $order = 0): ProductImage
{
    // saveQuietly avoids the real ProductGalleryObserver dispatch — these tests only
    // care about how the API serializes an already-placed gallery image.
    $image = new ProductImage(['product_id' => $product->id, 'path' => $path, 'order' => $order]);
    $image->saveQuietly();

    return $image;
}

test('a single product exposes its gallery images in order, with webp and thumbnail URLs', function () {
    $product = createProduct();
    $second = createGalleryImageQuietly($product, 'product-images/gallery/1/image.jpg', 1);
    $first = createGalleryImageQuietly($product, 'product-images/gallery/2/image.png', 0);

    $response = $this->getJson("/api/v1/products/{$product->id}");

    $response->assertOk();
    $response->assertJsonCount(2, 'data.gallery');
    $response->assertJsonPath('data.gallery.0.webp_url', fn ($url) => str_ends_with($url, "product-images/gallery/{$first->id}/image.webp"));
    $response->assertJsonPath('data.gallery.0.thumbnail_webp_url', fn ($url) => str_ends_with($url, "product-images/gallery/{$first->id}/image-thumbnail.webp"));
    $response->assertJsonPath('data.gallery.1.webp_url', fn ($url) => str_ends_with($url, "product-images/gallery/{$second->id}/image.webp"));
});

test('a product without gallery images exposes an empty gallery array', function () {
    $product = createProduct();

    $response = $this->getJson("/api/v1/products/{$product->id}");

    $response->assertOk();
    $response->assertJsonPath('data.gallery', []);
});

test('the products index does not eager-load or expose the gallery', function () {
    $product = createProduct();
    createGalleryImageQuietly($product, 'product-images/gallery/1/image.jpg');

    $response = $this->getJson('/api/v1/products');

    $response->assertOk();
    $response->assertJsonMissingPath('data.0.gallery');
});

test('retrieving a non-existent product returns 404', function () {
    $response = $this->getJson('/api/v1/products/999');

    $response->assertNotFound();
});

test('the Accept-Language header switches the translated fields', function () {
    $product = createProduct();
    $product->setTranslation('name', 'pl', 'Pralka');
    $product->setTranslation('description', 'pl', 'Pralka ładowana od przodu.');
    $product->save();

    $response = $this->getJson("/api/v1/products/{$product->id}", ['Accept-Language' => 'pl']);

    $response->assertOk();
    $response->assertJsonPath('data.name', 'Pralka');
    $response->assertJsonPath('data.description', 'Pralka ładowana od przodu.');
});

test('an unsupported Accept-Language falls back to the default locale', function () {
    $product = createProduct();

    $response = $this->getJson("/api/v1/products/{$product->id}", ['Accept-Language' => 'de']);

    $response->assertOk();
    $response->assertJsonPath('data.name', 'Washing machine');
});

test('a select attribute name and option label follow the Accept-Language header', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    Attribute::create([
        'key' => 'color',
        'name' => ['en' => 'Color', 'pl' => 'Kolor'],
        'type' => AttributeType::Select,
        'options' => [
            ['key' => 'black', 'name' => ['en' => 'Black', 'pl' => 'Czarny']],
        ],
    ]);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Washing machine',
        'price_cents' => 199900,
        'currency' => 'PLN',
        'attributes' => ['color' => 'black'],
    ]);

    $response = $this->getJson("/api/v1/products/{$product->id}", ['Accept-Language' => 'pl']);

    $response->assertOk();
    $response->assertJsonPath('data.attributes.0', [
        'key' => 'color',
        'name' => 'Kolor',
        'value' => 'black',
        'value_label' => 'Czarny',
    ]);
});

test('a translatable text attribute value follows the Accept-Language header', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    Attribute::create([
        'key' => 'material',
        'name' => ['en' => 'Material', 'pl' => 'Materiał'],
        'type' => AttributeType::TextTranslatable,
    ]);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Washing machine',
        'price_cents' => 199900,
        'currency' => 'PLN',
        'attributes' => ['material' => ['en' => 'Cotton', 'pl' => 'Bawełna']],
    ]);

    $response = $this->getJson("/api/v1/products/{$product->id}", ['Accept-Language' => 'pl']);

    $response->assertOk();
    $response->assertJsonPath('data.attributes.0', [
        'key' => 'material',
        'name' => 'Materiał',
        'value' => 'Bawełna',
        'value_label' => 'Bawełna',
    ]);
});

test('a translatable text attribute value falls back to the default locale when missing a translation', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    Attribute::create([
        'key' => 'material',
        'name' => ['en' => 'Material', 'pl' => 'Materiał'],
        'type' => AttributeType::TextTranslatable,
    ]);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Washing machine',
        'price_cents' => 199900,
        'currency' => 'PLN',
        'attributes' => ['material' => ['en' => 'Cotton', 'pl' => null]],
    ]);

    $response = $this->getJson("/api/v1/products/{$product->id}", ['Accept-Language' => 'pl']);

    $response->assertOk();
    $response->assertJsonPath('data.attributes.0.value_label', 'Cotton');
});

