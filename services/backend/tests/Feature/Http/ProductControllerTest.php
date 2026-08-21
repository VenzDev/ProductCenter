<?php

use App\Models\Category;
use App\Models\Product;

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
            'attributes' => ['weight_kg' => 70],
        ],
    ]);
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

test('include=translations adds every language for translatable fields', function () {
    $product = createProduct();
    $product->setTranslation('name', 'pl', 'Pralka');
    $product->setTranslation('description', 'pl', 'Pralka ładowana od przodu.');
    $product->save();

    $response = $this->getJson("/api/v1/products/{$product->id}?include=translations");

    $response->assertOk();
    $response->assertJsonPath('data.name', 'Washing machine');
    $response->assertJsonPath('data.name_translations', [
        'en' => 'Washing machine',
        'pl' => 'Pralka',
    ]);
    $response->assertJsonPath('data.description_translations', [
        'en' => 'Front-loading washing machine.',
        'pl' => 'Pralka ładowana od przodu.',
    ]);
});

test('translations are omitted by default', function () {
    $product = createProduct();

    $response = $this->getJson("/api/v1/products/{$product->id}");

    $response->assertOk();
    $response->assertJsonMissingPath('data.name_translations');
    $response->assertJsonMissingPath('data.description_translations');
});
