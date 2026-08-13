<?php

use App\Jobs\GenerateProductDescriptionJob;
use App\Models\Category;
use App\Models\Product;

test('handling the job writes a placeholder description in the requested locale onto the product', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => ['en' => 'Widget', 'pl' => 'Gadżet'],
        'price_cents' => 1999,
        'currency' => 'PLN',
    ]);

    (new GenerateProductDescriptionJob($product->id, 'pl'))->handle();

    expect($product->fresh()->getTranslation('description', 'pl', false))
        ->toBe('Gadżet — description coming soon.');
});

test('a job for a product that no longer exists does nothing without throwing', function () {
    expect(fn () => (new GenerateProductDescriptionJob(999999, 'en'))->handle())
        ->not->toThrow(Throwable::class);
});
