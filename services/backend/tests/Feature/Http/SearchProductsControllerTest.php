<?php

declare(strict_types=1);

use App\Images\Jobs\RelocateUploadedImageJob;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Product\Search\Index\ProductSearchIndexManager;
use Illuminate\Support\Facades\Bus;
use OpenSearch\Client;
use Tests\Factories\ProductFactory;

beforeEach(function () {
    Bus::fake([RelocateUploadedImageJob::class]);

    // Other test files also create products (via ProductFactory), which unconditionally
    // triggers ProductSearchObserver and indexes into OpenSearch — if one of those runs
    // right after this file's own afterEach deletes the index, OpenSearch auto-creates a
    // fresh one with its default dynamic mapping (no custom analyzers) on that write. So
    // don't trust whatever "products" index exists: delete it, then recreate it with the
    // real mapping these typo/synonym tests depend on.
    app(Client::class)->indices()->delete(['index' => 'products', 'client' => ['ignore' => [404]]]);
    app(ProductSearchIndexManager::class)->ensureIndexExists();
});

afterEach(function () {
    app(Client::class)->indices()->delete(['index' => 'products', 'client' => ['ignore' => [404]]]);
});

function createIndexedProduct(array $attributes): Product
{
    $product = ProductFactory::new()->create($attributes);

    // Search (unlike a get-by-id) is only near-real-time, so force a refresh to make
    // the document visible to the query the test is about to run.
    app(Client::class)->indices()->refresh(['index' => 'products']);

    return $product;
}

test('products can be found by name', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

    $washingMachine = createIndexedProduct(['category_id' => $category->id, 'name' => 'Washing machine']);
    createIndexedProduct(['category_id' => $category->id, 'name' => 'Bluetooth speaker']);

    $response = $this->getJson('/api/v1/products/search?q=washing');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.id', $washingMachine->id);
});

test('a Polish typo still finds the product', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $phone = createIndexedProduct(['category_id' => $category->id, 'name' => ['pl' => 'Telefon Samsung']]);

    $response = $this->getJson('/api/v1/products/search?q=tellefon');

    $response->assertOk();
    $response->assertJsonPath('data.0.id', $phone->id);
});

test('a Polish synonym finds the product', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $phone = createIndexedProduct(['category_id' => $category->id, 'name' => ['pl' => 'Telefon Samsung']]);

    $response = $this->getJson('/api/v1/products/search?q=smartfon');

    $response->assertOk();
    $response->assertJsonPath('data.0.id', $phone->id);
});

test('an English typo still finds the product', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $phone = createIndexedProduct(['category_id' => $category->id, 'name' => ['en' => 'Wireless Phone']]);

    $response = $this->getJson('/api/v1/products/search?q=phome');

    $response->assertOk();
    $response->assertJsonPath('data.0.id', $phone->id);
});

test('an English synonym finds the product', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $phone = createIndexedProduct(['category_id' => $category->id, 'name' => ['en' => 'Wireless Phone']]);

    $response = $this->getJson('/api/v1/products/search?q=smartphone');

    $response->assertOk();
    $response->assertJsonPath('data.0.id', $phone->id);
});

test('search requires a query string', function () {
    $response = $this->getJson('/api/v1/products/search');

    $response->assertUnprocessable();
});

test('search returns an empty list when nothing has been indexed yet', function () {
    $response = $this->getJson('/api/v1/products/search?q=anything');

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
});

test('the categories filter lists every category matched by the query, with counts', function () {
    $electronics = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $furniture = Category::create(['name' => 'Furniture', 'slug' => 'furniture']);

    createIndexedProduct(['category_id' => $electronics->id, 'name' => 'Wireless phone']);
    createIndexedProduct(['category_id' => $electronics->id, 'name' => 'Wireless charger']);
    createIndexedProduct(['category_id' => $furniture->id, 'name' => 'Wireless doorbell chair']);

    $response = $this->getJson('/api/v1/products/search?q=wireless');

    $response->assertOk();
    $response->assertJsonCount(3, 'data');

    $byId = collect($response->json('filters.categories'))->keyBy('id');
    expect($byId[$electronics->id]['count'])->toBe(2);
    expect($byId[$furniture->id]['count'])->toBe(1);
});

test('narrowing to a category filters results and exposes its attribute facets', function () {
    $electronics = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $furniture = Category::create(['name' => 'Furniture', 'slug' => 'furniture']);
    $color = Attribute::create([
        'key' => 'color',
        'name' => 'Color',
        'type' => 'select',
        'filterable' => true,
        'options' => [
            ['key' => 'red', 'name' => ['en' => 'Red']],
            ['key' => 'blue', 'name' => ['en' => 'Blue']],
        ],
    ]);
    $electronics->attributes()->attach($color->id);

    $phone = createIndexedProduct(['category_id' => $electronics->id, 'name' => 'Wireless phone', 'attributes' => ['color' => 'red']]);
    createIndexedProduct(['category_id' => $furniture->id, 'name' => 'Wireless doorbell chair']);

    $response = $this->getJson("/api/v1/products/search?q=wireless&category_id={$electronics->id}");

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.id', $phone->id);

    $attributeFacets = collect($response->json('filters.attributes'));
    expect($attributeFacets->pluck('key')->all())->toBe(['color']);
});

test('narrowing to a parent category also includes its subcategory\'s matching products', function () {
    $parent = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $child = Category::create(['name' => 'Phones', 'slug' => 'phones', 'parent_id' => $parent->id]);

    $ownProduct = createIndexedProduct(['category_id' => $parent->id, 'name' => 'Wireless charger']);
    $childProduct = createIndexedProduct(['category_id' => $child->id, 'name' => 'Wireless phone']);

    $response = $this->getJson("/api/v1/products/search?q=wireless&category_id={$parent->id}");

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    expect(collect($response->json('data'))->pluck('id')->all())
        ->toEqualCanonicalizing([$ownProduct->id, $childProduct->id]);
});

test('the attributes filter is empty when the search isn\'t narrowed to a category', function () {
    $response = $this->getJson('/api/v1/products/search?q=anything');

    $response->assertOk();
    $response->assertJsonPath('filters.attributes', []);
});

test('an unknown category_id is rejected', function () {
    $response = $this->getJson('/api/v1/products/search?q=anything&category_id=999999');

    $response->assertUnprocessable();
});

test('search results can be sorted by price', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

    $cheap = createIndexedProduct(['category_id' => $category->id, 'name' => 'Wireless cheap', 'price_cents' => 1000]);
    $expensive = createIndexedProduct(['category_id' => $category->id, 'name' => 'Wireless expensive', 'price_cents' => 9000]);

    $response = $this->getJson('/api/v1/products/search?q=wireless&sort=price_desc');

    $response->assertOk();
    $response->assertJsonPath('data.0.id', $expensive->id);
    $response->assertJsonPath('data.1.id', $cheap->id);
});
