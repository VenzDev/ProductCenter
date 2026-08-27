<?php

declare(strict_types=1);

use App\Models\Admin;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\Factories\AdminFactory;

test('an admin can log in via microsoft and gets a session', function () {
    $admin = AdminFactory::new()->create(['microsoft_id' => 'oid-admin']);

    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = 'oid-admin';

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

    $response = $this->get('/auth/microsoft/callback');

    $response->assertRedirect('/admin');
    $this->assertAuthenticatedAs($admin, 'admin');
});

test('a microsoft login is rejected when no matching admin account exists', function () {
    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = 'oid-unknown';

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

    $response = $this->get('/auth/microsoft/callback');

    $response->assertForbidden();
    $this->assertGuest('admin');
});

test('an admin is provisioned just-in-time when the email domain matches the allowed tenant domain', function () {
    config(['services.microsoft.allowed_domain' => 'example.com']);

    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = 'oid-new';
    $socialiteUser->name = 'New Admin';
    $socialiteUser->email = 'new.admin@example.com';

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

    $response = $this->get('/auth/microsoft/callback');

    $response->assertRedirect('/admin');
    $admin = Admin::where('microsoft_id', 'oid-new')->first();
    expect($admin)->not->toBeNull();
    expect($admin->email)->toBe('new.admin@example.com');
    $this->assertAuthenticatedAs($admin, 'admin');
});

test('a microsoft login is still rejected when the email domain does not match the allowed tenant domain', function () {
    config(['services.microsoft.allowed_domain' => 'example.com']);

    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = 'oid-outsider';
    $socialiteUser->name = 'Outsider';
    $socialiteUser->email = 'outsider@other.com';

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

    $response = $this->get('/auth/microsoft/callback');

    $response->assertForbidden();
    $this->assertGuest('admin');
    expect(Admin::where('microsoft_id', 'oid-outsider')->exists())->toBeFalse();
});
