<?php

declare(strict_types=1);

use App\Models\User;

test('a user can register and receives a jwt', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'secret1234',
    ]);

    $response->assertCreated()->assertJsonStructure(['access_token', 'token_type', 'expires_in']);

    expect(User::where('email', 'jane@example.com')->exists())->toBeTrue();
});

test('registration fails with a duplicate email', function () {
    User::factory()->create(['email' => 'jane@example.com']);

    $response = $this->postJson('/api/v1/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'secret1234',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('email');
});

test('registration fails with a short password', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'short',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('password');
});
