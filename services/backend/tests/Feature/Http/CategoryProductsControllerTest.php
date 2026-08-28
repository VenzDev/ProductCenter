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

    // Other test files also create products without cleaning up the OpenSearch index
    // afterwards (see SearchProductsControllerTest for the full explanation) — start
    // from a known-good, freshly-mapped index rather than trusting whatever exists.
    app(Client::class)->indices()->delete(['index' => 'products', 'client' => ['ignore' => [404]]]);
    app(ProductSearchIndexManager::class)->ensureIndexExists();
});

afterEach(function () {
    app(Client::class)->indices()->delete(['index' => 'products', 'client' => ['ignore' => [404]]]);
});

/**
 * @param  array<string, mixed>  $attributes
 */
function createIndexedProductIn(Category $category, array $attributes = []): Product
{
    $product = ProductFactory::new()->create([...$attributes, 'category_id' => $category->id]);

    // Search is only near-real-time, so force a refresh to make the document visible
    // to the query the test is about to run.
    app(Client::class)->indices()->refresh(['index' => 'products']);

    return $product;
}

test('lists only products in the requested category', function () {
    $electronics = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $furniture = Category::create(['name' => 'Furniture', 'slug' => 'furniture']);

    $laptop = createIndexedProductIn($electronics, ['name' => 'Laptop']);
    createIndexedProductIn($furniture, ['name' => 'Chair']);

    $response = $this->getJson("/api/v1/categories/{$electronics->slug}/products");

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.id', $laptop->id);
});

test('a subcategory slug containing a slash still resolves', function () {
    $parent = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $child = Category::create(['name' => 'Phones', 'slug' => 'phones', 'parent_id' => $parent->id]);

    $phone = createIndexedProductIn($child, ['name' => 'Phone']);

    $response = $this->getJson("/api/v1/categories/{$child->slug}/products");

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.id', $phone->id);
});

test('a parent category listing includes its subcategory\'s products', function () {
    $parent = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $child = Category::create(['name' => 'Phones', 'slug' => 'phones', 'parent_id' => $parent->id]);

    $ownProduct = createIndexedProductIn($parent, ['name' => 'Charger']);
    $childProduct = createIndexedProductIn($child, ['name' => 'Phone']);

    $response = $this->getJson("/api/v1/categories/{$parent->slug}/products");

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    expect(collect($response->json('data'))->pluck('id')->all())
        ->toEqualCanonicalizing([$ownProduct->id, $childProduct->id]);
});

test('a parent category lists its subcategories with their product counts', function () {
    $parent = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $phones = Category::create(['name' => 'Phones', 'slug' => 'phones', 'parent_id' => $parent->id]);
    $laptops = Category::create(['name' => 'Laptops', 'slug' => 'laptops', 'parent_id' => $parent->id]);

    createIndexedProductIn($phones, ['name' => 'Phone one']);
    createIndexedProductIn($phones, ['name' => 'Phone two']);
    createIndexedProductIn($laptops, ['name' => 'Laptop']);

    $response = $this->getJson("/api/v1/categories/{$parent->slug}/products");

    $response->assertOk();
    $byId = collect($response->json('filters.subcategories'))->keyBy('id');
    expect($byId[$phones->id])->toBe(['id' => $phones->id, 'slug' => 'electronics/phones', 'name' => 'Phones', 'count' => 2]);
    expect($byId[$laptops->id])->toBe(['id' => $laptops->id, 'slug' => 'electronics/laptops', 'name' => 'Laptops', 'count' => 1]);
});

test('a subcategory listing only includes its own products, not its parent\'s', function () {
    $parent = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $child = Category::create(['name' => 'Phones', 'slug' => 'phones', 'parent_id' => $parent->id]);

    createIndexedProductIn($parent, ['name' => 'Charger']);
    $childProduct = createIndexedProductIn($child, ['name' => 'Phone']);

    $response = $this->getJson("/api/v1/categories/{$child->slug}/products");

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.id', $childProduct->id);
});

test('a parent category\'s attribute facets include one assigned only to a subcategory', function () {
    $parent = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $child = Category::create(['name' => 'Phones', 'slug' => 'phones', 'parent_id' => $parent->id]);
    $storage = Attribute::create([
        'key' => 'storage_gb',
        'name' => 'Storage',
        'type' => 'number',
        'filterable' => true,
    ]);
    $child->attributes()->attach($storage->id);

    createIndexedProductIn($child, ['attributes' => ['storage_gb' => 128]]);

    $response = $this->getJson("/api/v1/categories/{$parent->slug}/products");

    $response->assertOk();
    $attributeKeys = collect($response->json('filters.attributes'))->pluck('key')->all();
    expect($attributeKeys)->toContain('storage_gb');
});

test('returns 404 for an unknown category slug', function () {
    $response = $this->getJson('/api/v1/categories/does-not-exist/products');

    $response->assertNotFound();
});

test('filters by price range', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

    $cheap = createIndexedProductIn($category, ['name' => 'Cheap', 'price_cents' => 1000]);
    createIndexedProductIn($category, ['name' => 'Expensive', 'price_cents' => 9000]);

    $response = $this->getJson("/api/v1/categories/{$category->slug}/products?price_max=5000");

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.id', $cheap->id);
});

test('filters by a selected attribute value', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
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
    $category->attributes()->attach($color->id);

    $red = createIndexedProductIn($category, ['name' => 'Red phone', 'attributes' => ['color' => 'red']]);
    createIndexedProductIn($category, ['name' => 'Blue phone', 'attributes' => ['color' => 'blue']]);

    $query = http_build_query(['attr' => ['color' => ['red']]]);
    $response = $this->getJson("/api/v1/categories/{$category->slug}/products?{$query}");

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.id', $red->id);
});

test('facet counts follow multi-select faceting: selecting one option keeps sibling counts visible', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
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
    $category->attributes()->attach($color->id);

    createIndexedProductIn($category, ['attributes' => ['color' => 'red']]);
    createIndexedProductIn($category, ['attributes' => ['color' => 'red']]);
    createIndexedProductIn($category, ['attributes' => ['color' => 'blue']]);

    $query = http_build_query(['attr' => ['color' => ['red']]]);
    $response = $this->getJson("/api/v1/categories/{$category->slug}/products?{$query}");

    $response->assertOk();
    $response->assertJsonCount(2, 'data');

    $options = collect($response->json('filters.attributes'))->firstWhere('key', 'color')['options'];
    $byKey = collect($options)->keyBy('key');

    expect($byKey['red']['count'])->toBe(2);
    expect($byKey['blue']['count'])->toBe(1);
});

test('a non-filterable attribute in the query string is ignored', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $material = Attribute::create(['key' => 'material', 'name' => 'Material', 'type' => 'text', 'filterable' => false]);
    $category->attributes()->attach($material->id);

    $product = createIndexedProductIn($category, ['attributes' => ['material' => 'wood']]);

    $query = http_build_query(['attr' => ['material' => ['plastic']]]);
    $response = $this->getJson("/api/v1/categories/{$category->slug}/products?{$query}");

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.id', $product->id);
});

test('the price filter block reports the min/max across matching products', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

    createIndexedProductIn($category, ['price_cents' => 1000]);
    createIndexedProductIn($category, ['price_cents' => 5000]);

    $response = $this->getJson("/api/v1/categories/{$category->slug}/products");

    $response->assertOk();
    $response->assertJsonPath('filters.price.min', 1000);
    $response->assertJsonPath('filters.price.max', 5000);
});

test('products can be sorted by price descending', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

    $cheap = createIndexedProductIn($category, ['price_cents' => 1000]);
    $expensive = createIndexedProductIn($category, ['price_cents' => 5000]);

    $response = $this->getJson("/api/v1/categories/{$category->slug}/products?sort=price_desc");

    $response->assertOk();
    $response->assertJsonPath('data.0.id', $expensive->id);
    $response->assertJsonPath('data.1.id', $cheap->id);
});

test('an invalid sort value is rejected', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

    $response = $this->getJson("/api/v1/categories/{$category->slug}/products?sort=bogus");

    $response->assertUnprocessable();
});
