<?php

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use Aws\Result;
use Aws\Sqs\SqsClient;
use Livewire\Livewire;

test('generating a description from the view page publishes the product DTO for the chosen locale', function () {
    $client = Mockery::mock(SqsClient::class);
    $client->shouldReceive('getQueueUrl')
        ->once()
        ->andReturn(new Result(['QueueUrl' => 'http://localstack:4566/000000000000/product-description-requested']));
    $client->shouldReceive('sendMessage')
        ->once()
        ->with(Mockery::on(function (array $args) {
            $body = json_decode($args['MessageBody'], true);

            return $body['locale'] === 'pl'
                && $body['name'] === ['en' => 'Widget', 'pl' => 'Gadżet']
                && $body['attributes'] === ['weight_kg' => 1.2];
        }));
    app()->instance(SqsClient::class, $client);

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
});

test('the button is also available from the edit page', function () {
    app()->instance(SqsClient::class, Mockery::mock(SqsClient::class, [
        'getQueueUrl' => new Result(['QueueUrl' => 'http://localstack:4566/000000000000/product-description-requested']),
        'sendMessage' => new Result([]),
    ]));

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
});
