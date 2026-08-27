<?php

declare(strict_types=1);

use App\Images\Jobs\RelocateUploadedImageJob;
use App\Models\Category;
use App\Models\Product;
use App\Product\Search\ProductSearchIndexManager;
use Illuminate\Support\Facades\Bus;
use OpenSearch\Client;
use Tests\Factories\ProductFactory;

beforeEach(function () {
    Bus::fake([RelocateUploadedImageJob::class]);

    // The index is dropped in afterEach below, so each test needs it recreated with its
    // real mapping — otherwise OpenSearch would dynamically map the first indexed
    // document, without the per-language analyzers these tests rely on.
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
