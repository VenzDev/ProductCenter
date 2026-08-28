<?php

declare(strict_types=1);

use App\Images\Jobs\RelocateUploadedImageJob;
use App\Models\Attribute;
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

test('creating a product indexes it in OpenSearch', function () {
    $product = ProductFactory::new()->create([
        'name' => 'Washing machine',
        'description' => 'Front-loading washing machine.',
    ]);

    $document = app(Client::class)->get(['index' => 'products', 'id' => (string) $product->id]);

    expect($document['_source']['name'])->toBe(['en' => 'Washing machine']);
    expect($document['_source']['description'])->toBe(['en' => 'Front-loading washing machine.']);
    expect($document['_source']['category_id'])->toBe($product->category_id);
    expect($document['_source']['price_cents'])->toBe($product->price_cents);
});

test('only filterable attribute values are indexed', function () {
    Attribute::create(['key' => 'weight_kg', 'name' => 'Weight', 'type' => 'number', 'filterable' => true]);
    Attribute::create(['key' => 'material', 'name' => 'Material', 'type' => 'text', 'filterable' => false]);

    $product = ProductFactory::new()->create([
        'attributes' => ['weight_kg' => 1.2, 'material' => 'Cotton'],
    ]);

    $document = app(Client::class)->get(['index' => 'products', 'id' => (string) $product->id]);

    expect($document['_source']['attributes'])->toBe(['weight_kg' => 1.2]);
});

test('updating a product reindexes it', function () {
    $product = ProductFactory::new()->create(['name' => 'Washing machine']);

    $product->update(['name' => 'Front-loading washing machine']);

    $document = app(Client::class)->get(['index' => 'products', 'id' => (string) $product->id]);

    expect($document['_source']['name'])->toBe(['en' => 'Front-loading washing machine']);
});

test('deleting a product removes it from the index', function () {
    $product = ProductFactory::new()->create(['name' => 'Washing machine']);

    $product->delete();

    $exists = app(Client::class)->exists(['index' => 'products', 'id' => (string) $product->id]);

    expect($exists)->toBeFalse();
});
