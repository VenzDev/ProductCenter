<?php

declare(strict_types=1);

use App\Models\Order;
use App\Payment\Gateway\PaymentGateway;
use Tests\Factories\UserFactory;
use Tests\Fakes\FakePaymentGateway;

beforeEach(function () {
    $this->app->instance(PaymentGateway::class, new FakePaymentGateway);
});

test('a payment_intent.succeeded webhook marks a pending order as paid', function () {
    $order = Order::create([
        'user_id' => UserFactory::new()->create()->id,
        'status' => 'pending',
        'total_cents' => 1000,
        'currency' => 'PLN',
        'payment_reference' => 'pi_fake_123',
    ]);

    $response = $this->postJson('/api/v1/stripe/webhook', [
        'type' => 'payment_intent.succeeded',
        'payment_intent_id' => 'pi_fake_123',
    ]);

    $response->assertNoContent();
    expect($order->fresh()->status)->toBe('paid');
});

test('a payment_intent.payment_failed webhook marks a pending order as failed', function () {
    $order = Order::create([
        'user_id' => UserFactory::new()->create()->id,
        'status' => 'pending',
        'total_cents' => 1000,
        'currency' => 'PLN',
        'payment_reference' => 'pi_fake_456',
    ]);

    $response = $this->postJson('/api/v1/stripe/webhook', [
        'type' => 'payment_intent.payment_failed',
        'payment_intent_id' => 'pi_fake_456',
    ]);

    $response->assertNoContent();
    expect($order->fresh()->status)->toBe('failed');
});

test('a duplicate webhook does not reprocess an already-paid order', function () {
    $order = Order::create([
        'user_id' => UserFactory::new()->create()->id,
        'status' => 'paid',
        'total_cents' => 1000,
        'currency' => 'PLN',
        'payment_reference' => 'pi_fake_789',
    ]);

    $response = $this->postJson('/api/v1/stripe/webhook', [
        'type' => 'payment_intent.payment_failed',
        'payment_intent_id' => 'pi_fake_789',
    ]);

    $response->assertNoContent();
    expect($order->fresh()->status)->toBe('paid');
});
