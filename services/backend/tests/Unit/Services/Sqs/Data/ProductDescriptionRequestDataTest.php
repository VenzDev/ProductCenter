<?php

use App\Models\Product;
use App\Services\Sqs\Data\ProductDescriptionRequestData;

test('fromProduct picks up the product id, translations, and attributes for the given locale', function () {
    $product = new Product([
        'name' => ['en' => 'Widget', 'pl' => 'Gadżet'],
        'attributes' => ['weight_kg' => 1.2],
    ]);
    $product->id = 42;

    $data = ProductDescriptionRequestData::fromProduct($product, 'pl');

    expect($data->productId)->toBe(42);
    expect($data->locale)->toBe('pl');
    expect($data->name)->toBe(['en' => 'Widget', 'pl' => 'Gadżet']);
    expect($data->attributes)->toBe(['weight_kg' => 1.2]);
});

test('the DTO serializes to the expected wire format', function () {
    $product = new Product(['name' => ['en' => 'Widget'], 'attributes' => null]);
    $product->id = 7;

    $data = ProductDescriptionRequestData::fromProduct($product, 'en');

    expect($data->jsonSerialize())->toBe([
        'product_id' => 7,
        'locale' => 'en',
        'name' => ['en' => 'Widget'],
        'attributes' => null,
    ]);
});
