<?php

declare(strict_types=1);

use App\Ai\Jobs\GenerateProductDescriptionJob;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;

test('handling the job writes the AI-generated description in the requested locale onto the product', function () {
    Storage::fake('s3');
    Prism::fake([
        TextResponseFake::make()->withText('Gadżet to lekkie i wytrzymałe urządzenie.'),
    ]);

    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    // withoutEvents skips ProductImageObserver's relocation dispatch — this test only
    // covers the no-webp-yet branch, so main_image is just a required placeholder.
    $product = Product::withoutEvents(fn () => Product::create([
        'category_id' => $category->id,
        'name' => ['en' => 'Widget', 'pl' => 'Gadżet'],
        'price_cents' => 1999,
        'currency' => 'PLN',
        'attributes' => ['weight_kg' => 1.2],
        'main_image' => 'product-images/placeholder/main-image.jpg',
    ]));

    app()->call([new GenerateProductDescriptionJob($product->id, 'pl'), 'handle']);

    expect($product->fresh()->getTranslation('description', 'pl', false))
        ->toBe('Gadżet to lekkie i wytrzymałe urządzenie.');
});

test('handling the job includes the main image when one is stored on S3', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('product-images/1/main-image.webp', 'fake-webp-bytes');
    Prism::fake([
        TextResponseFake::make()->withText('A sturdy, lightweight widget.'),
    ]);

    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'id' => 1,
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
        'main_image' => true,
    ]);

    app()->call([new GenerateProductDescriptionJob($product->id, 'en'), 'handle']);

    expect($product->fresh()->getTranslation('description', 'en', false))
        ->toBe('A sturdy, lightweight widget.');
});

test('handling the job resolves attribute names and option labels in the requested locale', function () {
    Attribute::create([
        'key' => 'color',
        'name' => ['en' => 'Color', 'pl' => 'Kolor'],
        'type' => 'select',
        'options' => [
            ['key' => 'red', 'name' => ['en' => 'Red', 'pl' => 'Czerwony']],
        ],
    ]);

    Storage::fake('s3');
    $fake = Prism::fake([
        TextResponseFake::make()->withText('Opis produktu.'),
    ]);

    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::withoutEvents(fn () => Product::create([
        'category_id' => $category->id,
        'name' => ['en' => 'Widget', 'pl' => 'Gadżet'],
        'price_cents' => 1999,
        'currency' => 'PLN',
        'attributes' => ['color' => 'red'],
        'main_image' => 'product-images/placeholder/main-image.jpg',
    ]));

    app()->call([new GenerateProductDescriptionJob($product->id, 'pl'), 'handle']);

    $fake->assertRequest(function (array $requests) {
        expect($requests[0]->prompt())->toContain('Kolor: Czerwony');
    });
});

test('a job for a product that no longer exists does nothing without throwing', function () {
    expect(fn () => app()->call([new GenerateProductDescriptionJob(999999, 'en'), 'handle']))
        ->not->toThrow(Throwable::class);
});
