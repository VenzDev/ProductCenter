<?php

use App\Models\Category;
use App\Models\Product;
use App\Services\Sqs\ProductDescriptionGeneratedConsumer;
use Opis\JsonSchema\Validator;

function productDescriptionGeneratedConsumer(): ProductDescriptionGeneratedConsumer
{
    return new ProductDescriptionGeneratedConsumer(new Validator);
}

test('a valid message writes the description onto the product and reports it handled', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
    ]);

    $body = json_encode([
        'product_id' => $product->id,
        'locale' => 'pl',
        'description' => 'Świetny gadżet.',
    ]);

    $shouldDelete = productDescriptionGeneratedConsumer()->consume($body);

    expect($shouldDelete)->toBeTrue();
    expect($product->fresh()->getTranslation('description', 'pl', false))->toBe('Świetny gadżet.');
});

test('a message that fails contract validation is left untouched and reported handled', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
    ]);

    $body = json_encode([
        'product_id' => $product->id,
        'locale' => 'de', // unsupported locale — fails the contract
        'description' => 'Ein tolles Gadget.',
    ]);

    $shouldDelete = productDescriptionGeneratedConsumer()->consume($body);

    expect($shouldDelete)->toBeTrue();
    expect($product->fresh()->getAttribute('description'))->toBeNull();
});

test('a message for a product that no longer exists is reported handled without throwing', function () {
    $body = json_encode([
        'product_id' => 999999,
        'locale' => 'en',
        'description' => 'Does not matter.',
    ]);

    $shouldDelete = productDescriptionGeneratedConsumer()->consume($body);

    expect($shouldDelete)->toBeTrue();
});
