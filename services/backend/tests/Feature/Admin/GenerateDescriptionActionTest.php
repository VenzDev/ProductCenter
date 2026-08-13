<?php

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Jobs\GenerateProductDescriptionJob;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('generating a description from the view page dispatches the job for the chosen locale', function () {
    Queue::fake();

    $admin = Admin::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => ['en' => 'Widget', 'pl' => 'Gadżet'],
        'price_cents' => 1999,
        'currency' => 'PLN',
        'attributes' => ['weight_kg' => 1.2],
    ]);
    $this->actingAs($admin, 'admin');

    Livewire::test(ViewProduct::class, ['record' => $product->getRouteKey()])
        ->callAction('generateDescription', ['locale' => 'pl'])
        ->assertHasNoActionErrors();

    Queue::assertPushed(GenerateProductDescriptionJob::class, fn (GenerateProductDescriptionJob $job) => $job->productId === $product->id && $job->locale === 'pl');
});

test('the button is also available from the edit page', function () {
    Queue::fake();

    $admin = Admin::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
    ]);
    $this->actingAs($admin, 'admin');

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->callAction('generateDescription', ['locale' => 'en'])
        ->assertHasNoActionErrors();

    Queue::assertPushed(GenerateProductDescriptionJob::class, fn (GenerateProductDescriptionJob $job) => $job->productId === $product->id && $job->locale === 'en');
});
