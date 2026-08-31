<?php

declare(strict_types=1);

use App\Enums\AttributeType;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Product\Support\ProductImagePaths;
use App\Storage\StorageDisk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Factories\AdminFactory;
use Tests\Factories\ProductFactory;

test('an admin can list products', function () {
    $admin = AdminFactory::new()->create();
    ProductFactory::new()->createQuietly();

    $response = $this->actingAs($admin, 'admin')->get('/admin/products');

    $response->assertOk()->assertSee('Widget');
});

test('an admin can create a product with translations for both locales', function () {
    Storage::fake(StorageDisk::S3);
    $admin = AdminFactory::new()->create();
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
            'main_image' => UploadedFile::fake()->image('product.jpg'),
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
    $admin = AdminFactory::new()->create();
    $product = ProductFactory::new()->createQuietly([
        'name' => ['en' => 'Widget', 'pl' => 'Gadżet'],
        'description' => ['en' => 'A fine widget', 'pl' => 'Świetny gadżet'],
    ]);
    $this->actingAs($admin, 'admin');

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->assertSchemaStateSet([
            'name.en' => 'Widget',
            'name.pl' => 'Gadżet',
            'description.en' => 'A fine widget',
            'description.pl' => 'Świetny gadżet',
        ]);
});

test('an admin can update a product name/description per locale', function () {
    Storage::fake(StorageDisk::S3);
    Storage::disk(StorageDisk::S3)->put('product-images/placeholder/main-image.jpg', 'fake-bytes');

    $admin = AdminFactory::new()->create();
    $product = ProductFactory::new()->createQuietly();
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
    $admin = AdminFactory::new()->create();
    $product = ProductFactory::new()->createQuietly([
        'name' => ['en' => 'Widget', 'pl' => 'Gadżet'],
        'description' => ['en' => 'A fine widget', 'pl' => 'Świetny gadżet'],
    ]);

    $response = $this->actingAs($admin, 'admin')->get("/admin/products/{$product->id}");

    $response->assertOk()
        ->assertSee('Widget')->assertSee('Gadżet')
        ->assertSee('A fine widget')->assertSee('Świetny gadżet');
});

test('an admin can upload a product image to the s3 disk', function () {
    Storage::fake(StorageDisk::S3);

    $admin = AdminFactory::new()->create();
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
    expect($product->main_image)->toBe("product-images/{$product->id}/main-image.jpg");
    Storage::disk(StorageDisk::S3)->assertExists($product->main_image);

    // RelocateUploadedImageJob and the GenerateWebpImageJob it dispatches both run
    // synchronously (sync queue driver in tests), so their output is already in place
    // once the form submission above returns.
    Storage::disk(StorageDisk::S3)->assertExists(ProductImagePaths::webp($product->id));
    Storage::disk(StorageDisk::S3)->assertExists(ProductImagePaths::thumbnailWebp($product->id));
});

test('the attributes repeater starts empty on create before a category is chosen', function () {
    $admin = AdminFactory::new()->create();
    $this->actingAs($admin, 'admin');

    $component = Livewire::test(CreateProduct::class);

    expect($component->get('data.attributes'))->toBe([]);
});

test('selecting a category on create preinitializes the attributes repeater from it', function () {
    $admin = AdminFactory::new()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $weight = Attribute::create(['key' => 'weight_kg', 'name' => 'Weight', 'type' => AttributeType::Number]);
    $color = Attribute::create(['key' => 'color', 'name' => 'Color', 'type' => AttributeType::Select, 'options' => [
        ['key' => 'red', 'name' => ['en' => 'Red', 'pl' => 'Czerwony']],
        ['key' => 'blue', 'name' => ['en' => 'Blue', 'pl' => 'Niebieski']],
    ]]);
    $category->attributes()->sync([$weight->id, $color->id]);
    $this->actingAs($admin, 'admin');

    $component = Livewire::test(CreateProduct::class)
        ->fillForm(['category_id' => $category->id]);

    $rows = collect($component->get('data.attributes'))->sortBy('key')->values()->all();
    expect($rows)->toBe([
        ['key' => 'color', 'value' => null],
        ['key' => 'weight_kg', 'value' => null],
    ]);
});

test('changing the category on an existing product does not touch its saved attributes', function () {
    $admin = AdminFactory::new()->create();
    $otherCategory = Category::create(['name' => 'Books', 'slug' => 'books']);
    Attribute::create(['key' => 'weight_kg', 'name' => 'Weight', 'type' => AttributeType::Number]);
    $product = ProductFactory::new()->createQuietly(['attributes' => ['weight_kg' => '1.2']]);
    $this->actingAs($admin, 'admin');

    $component = Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm(['category_id' => $otherCategory->id]);

    expect(array_values($component->get('data.attributes')))->toBe([
        ['key' => 'weight_kg', 'value' => '1.2', 'value_translations' => [
            'en' => null, 'pl' => null, 'de' => null, 'fr' => null, 'it' => null,
        ]],
    ]);
});

test('an admin can create a product with a number and a select attribute value', function () {
    Storage::fake(StorageDisk::S3);
    $admin = AdminFactory::new()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    Attribute::create(['key' => 'weight_kg', 'name' => 'Weight', 'type' => AttributeType::Number]);
    Attribute::create(['key' => 'color', 'name' => 'Color', 'type' => AttributeType::Select, 'options' => [
        ['key' => 'red', 'name' => ['en' => 'Red', 'pl' => 'Czerwony']],
        ['key' => 'blue', 'name' => ['en' => 'Blue', 'pl' => 'Niebieski']],
    ]]);
    $this->actingAs($admin, 'admin');

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'category_id' => $category->id,
            'name.en' => 'Widget',
            'price_cents' => 1999,
            'currency' => 'PLN',
            'attributes' => [
                ['key' => 'weight_kg', 'value' => '1.2'],
                ['key' => 'color', 'value' => 'red'],
            ],
            'main_image' => UploadedFile::fake()->image('product.jpg'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // jsonb doesn't preserve key order (unlike json), so compare sorted by key.
    $product = Product::first();
    expect(collect($product->attributes)->sortKeys()->all())
        ->toBe(['color' => 'red', 'weight_kg' => 1.2]);
});

test('a non-translatable attribute value survives even with an empty value_translations row', function () {
    // Regression test: the 'value_translations' Grid's fields are individually
    // dehydrated(false) for a non-translatable attribute, but Filament still leaves an
    // empty 'value_translations' array on the row — not null. A row must still save its
    // 'value' in that case, rather than a `$row['value_translations'] ?? $row['value']`
    // style fallback silently picking the empty array over the real value.
    Storage::fake(StorageDisk::S3);
    $admin = AdminFactory::new()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    Attribute::create(['key' => 'weight_kg', 'name' => 'Weight', 'type' => AttributeType::Number]);
    $this->actingAs($admin, 'admin');

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'category_id' => $category->id,
            'name.en' => 'Widget',
            'price_cents' => 1999,
            'currency' => 'PLN',
            'attributes' => [
                ['key' => 'weight_kg', 'value' => '1.2', 'value_translations' => []],
            ],
            'main_image' => UploadedFile::fake()->image('product.jpg'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::first();
    expect($product->attributes)->toBe(['weight_kg' => 1.2]);
});

test('an admin can create a product with a multiselect attribute value', function () {
    $admin = AdminFactory::new()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    Attribute::create(['key' => 'materials', 'name' => 'Materials', 'type' => AttributeType::MultiSelect, 'options' => [
        ['key' => 'wood', 'name' => ['en' => 'Wood', 'pl' => 'Drewno']],
        ['key' => 'metal', 'name' => ['en' => 'Metal', 'pl' => 'Metal']],
        ['key' => 'plastic', 'name' => ['en' => 'Plastic', 'pl' => 'Plastik']],
    ]]);
    Storage::fake(StorageDisk::S3);
    $this->actingAs($admin, 'admin');

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'category_id' => $category->id,
            'name.en' => 'Widget',
            'price_cents' => 1999,
            'currency' => 'PLN',
            'attributes' => [
                ['key' => 'materials', 'value' => ['wood', 'metal']],
            ],
            'main_image' => UploadedFile::fake()->image('product.jpg'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::first();
    expect($product->attributes)->toBe(['materials' => ['wood', 'metal']]);
});

test('an admin can create a product with a translatable text attribute value', function () {
    $admin = AdminFactory::new()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    Attribute::create(['key' => 'material', 'name' => 'Material', 'type' => AttributeType::TextTranslatable]);
    Storage::fake(StorageDisk::S3);
    $this->actingAs($admin, 'admin');

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'category_id' => $category->id,
            'name.en' => 'Widget',
            'price_cents' => 1999,
            'currency' => 'PLN',
            'attributes' => [
                ['key' => 'material', 'value_translations' => ['en' => 'Cotton', 'pl' => 'Bawełna']],
            ],
            'main_image' => UploadedFile::fake()->image('product.jpg'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::first();
    expect($product->attributes)->toBe([
        'material' => ['de' => null, 'en' => 'Cotton', 'fr' => null, 'it' => null, 'pl' => 'Bawełna'],
    ]);
});

test('an admin can remove an attribute row before creating a product', function () {
    Storage::fake(StorageDisk::S3);
    $admin = AdminFactory::new()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $this->actingAs($admin, 'admin');

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'category_id' => $category->id,
            'name.en' => 'Widget',
            'price_cents' => 1999,
            'currency' => 'PLN',
            'attributes' => [],
            'main_image' => UploadedFile::fake()->image('product.jpg'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::first();
    expect($product->attributes)->toBe([]);
});

test('editing a product pre-fills the attributes repeater from its saved values', function () {
    $admin = AdminFactory::new()->create();
    $product = ProductFactory::new()->createQuietly(['attributes' => ['weight_kg' => '1.2']]);
    $this->actingAs($admin, 'admin');

    $component = Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()]);

    expect(array_values($component->get('data.attributes')))->toBe([
        ['key' => 'weight_kg', 'value' => '1.2', 'value_translations' => [
            'en' => null, 'pl' => null, 'de' => null, 'fr' => null, 'it' => null,
        ]],
    ]);
});
