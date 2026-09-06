<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Order;
use App\Payment\Gateway\PaymentGateway;
use Tests\Factories\ProductFactory;
use Tests\Factories\UserFactory;
use Tests\Fakes\FakePaymentGateway;

beforeEach(function () {
    $this->app->instance(PaymentGateway::class, new FakePaymentGateway);
});

test('an authenticated user can check out their cart and receives a payment client secret', function () {
    $user = UserFactory::new()->create();
    $token = auth('api')->login($user);
    $product = ProductFactory::new()->create(['price_cents' => 1000, 'currency' => 'PLN']);

    $response = $this->postJson('/api/v1/checkout', [
        'items' => [['product_id' => $product->id, 'quantity' => 2]],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonStructure(['order_id', 'client_secret']);

    $order = Order::find($response->json('order_id'));
    expect($order)->not->toBeNull()
        ->and($order->user_id)->toBe($user->id)
        ->and($order->status)->toBe('pending')
        ->and($order->total_cents)->toBe(2000)
        ->and($order->currency)->toBe('PLN')
        ->and($order->payment_reference)->toBe('pi_fake_123')
        ->and($order->items)->toHaveCount(1);

    expect($order->items->first())
        ->product_id->toBe($product->id)
        ->quantity->toBe(2)
        ->unit_price_cents->toBe(1000);
});

test('checkout requires authentication', function () {
    $product = ProductFactory::new()->create();

    $response = $this->postJson('/api/v1/checkout', [
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ]);

    $response->assertUnauthorized();
});

test('checkout rejects an empty cart', function () {
    $user = UserFactory::new()->create();
    $token = auth('api')->login($user);

    $response = $this->postJson('/api/v1/checkout', [
        'items' => [],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertUnprocessable()->assertJsonValidationErrors('items');
});

test('checkout rejects a cart mixing currencies', function () {
    $user = UserFactory::new()->create();
    $token = auth('api')->login($user);
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $pln = ProductFactory::new()->create(['category_id' => $category->id, 'currency' => 'PLN']);
    $usd = ProductFactory::new()->create(['category_id' => $category->id, 'currency' => 'USD']);

    $response = $this->postJson('/api/v1/checkout', [
        'items' => [
            ['product_id' => $pln->id, 'quantity' => 1],
            ['product_id' => $usd->id, 'quantity' => 1],
        ],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertUnprocessable();
    expect(Order::count())->toBe(0);
});
