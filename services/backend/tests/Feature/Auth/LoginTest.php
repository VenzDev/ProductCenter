<?php

declare(strict_types=1);

use App\Models\User;

test('a user can log in with correct credentials and receives a jwt', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'secret1234',
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'jane@example.com',
        'password' => 'secret1234',
    ]);

    $response->assertOk()->assertJsonStructure(['access_token', 'token_type', 'expires_in']);
});

test('login fails with an incorrect password', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'secret1234',
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'jane@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized();
});

test('an authenticated user can fetch their own profile via /me', function () {
    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $response = $this->getJson('/api/v1/me', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJson(['id' => $user->id, 'email' => $user->email]);
});

test('an unauthenticated request to /me is rejected', function () {
    $response = $this->getJson('/api/v1/me');

    $response->assertUnauthorized();
});
