<?php

use App\Models\Admin;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('an admin can log in via microsoft and gets a session', function () {
    $admin = Admin::factory()->create(['microsoft_id' => 'oid-admin']);

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
