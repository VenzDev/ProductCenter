<?php

declare(strict_types=1);

use App\Images\Jobs\RelocateUploadedImageJob;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Bus;
use OpenSearch\Client;
use Tests\Factories\ProductFactory;

beforeEach(function () {
    // ProductFactory sets main_image, which makes ProductImageObserver dispatch a
    // real S3 relocation job (QUEUE_CONNECTION is sync) — irrelevant to search and
    // not available outside the local docker-compose stack (e.g. on CI).
    Bus::fake([RelocateUploadedImageJob::class]);
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

test('search requires a query string', function () {
    $response = $this->getJson('/api/v1/products/search');

    $response->assertUnprocessable();
});

test('search returns an empty list when nothing has been indexed yet', function () {
    $response = $this->getJson('/api/v1/products/search?q=anything');

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
});
