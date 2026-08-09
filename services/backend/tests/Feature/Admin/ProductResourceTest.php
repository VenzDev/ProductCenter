<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('an admin can list products', function () {
    $admin = Admin::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
    ]);

    $response = $this->actingAs($admin, 'admin')->get('/admin/products');

    $response->assertOk()->assertSee('Widget');
});

test('an admin can create a product with translations for both locales', function () {
    $admin = Admin::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $this->actingAs($admin, 'admin');

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'category_id' => $category->id,
            'name.en' => 'Widget',
            'name.pl' => 'Gadżet',
            'description.en' => 'A fine widget',
            'description.pl' => 'Świetny gadżet',
            'price_cents' => 1999,
            'currency' => 'PLN',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::first();
    expect($product)->not->toBeNull();
    expect($product->getTranslation('name', 'en', false))->toBe('Widget');
    expect($product->getTranslation('name', 'pl', false))->toBe('Gadżet');
    expect($product->getTranslation('description', 'pl', false))->toBe('Świetny gadżet');
    expect($product->category_id)->toBe($category->id);
});

test('editing a product pre-fills the name/description for each locale tab', function () {
    $admin = Admin::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => ['en' => 'Widget', 'pl' => 'Gadżet'],
        'description' => ['en' => 'A fine widget', 'pl' => 'Świetny gadżet'],
        'price_cents' => 1999,
        'currency' => 'PLN',
    ]);
    $this->actingAs($admin, 'admin');

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->assertFormSet([
            'name.en' => 'Widget',
            'name.pl' => 'Gadżet',
            'description.en' => 'A fine widget',
            'description.pl' => 'Świetny gadżet',
        ]);
});

test('an admin can update a product name/description per locale', function () {
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
        ->fillForm(['name.en' => 'Deluxe Widget', 'name.pl' => 'Luksusowy Gadżet', 'price_cents' => 2999])
        ->call('save')
        ->assertHasNoFormErrors();

    $product->refresh();
    expect($product->getTranslation('name', 'en', false))->toBe('Deluxe Widget');
    expect($product->getTranslation('name', 'pl', false))->toBe('Luksusowy Gadżet');
    expect($product->price_cents)->toBe(2999);
});

test('viewing a product shows the name/description for both locales', function () {
    $admin = Admin::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => ['en' => 'Widget', 'pl' => 'Gadżet'],
        'description' => ['en' => 'A fine widget', 'pl' => 'Świetny gadżet'],
        'price_cents' => 1999,
        'currency' => 'PLN',
    ]);

    $response = $this->actingAs($admin, 'admin')->get("/admin/products/{$product->id}");

    $response->assertOk()
        ->assertSee('Widget')->assertSee('Gadżet')
        ->assertSee('A fine widget')->assertSee('Świetny gadżet');
});

test('an admin can upload a product image to the s3 disk', function () {
    Storage::fake('s3');

    $admin = Admin::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $this->actingAs($admin, 'admin');
    $image = UploadedFile::fake()->image('product.jpg');

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'category_id' => $category->id,
            'name.en' => 'Widget',
            'price_cents' => 1999,
            'currency' => 'PLN',
            'main_image' => $image,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::first();
    expect($product->main_image)->not->toBeNull();
    expect($product->main_image)->toStartWith('products/');
    Storage::disk('s3')->assertExists($product->main_image);
});
