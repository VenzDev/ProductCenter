<?php

declare(strict_types=1);

use App\Auth\Admin\MicrosoftAdminResolver;
use App\Models\Admin;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\Factories\AdminFactory;

test('resolve returns the existing admin matched by microsoft_id without creating a duplicate', function () {
    config(['services.microsoft.allowed_domain' => 'example.com']);
    $admin = AdminFactory::new()->create(['microsoft_id' => 'oid-existing', 'email' => 'existing@example.com']);

    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = 'oid-existing';
    $socialiteUser->email = 'existing@example.com';
    $socialiteUser->name = 'Existing Admin';

    $resolved = (new MicrosoftAdminResolver)->resolve($socialiteUser);

    expect($resolved->is($admin))->toBeTrue();
    expect(Admin::count())->toBe(1);
});

test('resolve matches the allowed domain case-insensitively', function () {
    config(['services.microsoft.allowed_domain' => 'Example.COM']);

    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = 'oid-case';
    $socialiteUser->email = 'new.admin@EXAMPLE.com';
    $socialiteUser->name = 'Case Admin';

    $resolved = (new MicrosoftAdminResolver)->resolve($socialiteUser);

    expect($resolved)->not->toBeNull();
    expect($resolved->microsoft_id)->toBe('oid-case');
});

test('resolve does not provision an admin when the microsoft user has no email', function () {
    config(['services.microsoft.allowed_domain' => 'example.com']);

    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = 'oid-no-email';

    $resolved = (new MicrosoftAdminResolver)->resolve($socialiteUser);

    expect($resolved)->toBeNull();
    expect(Admin::count())->toBe(0);
});

test('resolving the same unknown user twice provisions only once', function () {
    config(['services.microsoft.allowed_domain' => 'example.com']);

    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = 'oid-repeat';
    $socialiteUser->email = 'repeat@example.com';
    $socialiteUser->name = 'Repeat Admin';

    $resolver = new MicrosoftAdminResolver;
    $first = $resolver->resolve($socialiteUser);
    $second = $resolver->resolve($socialiteUser);

    expect($second->is($first))->toBeTrue();
    expect(Admin::count())->toBe(1);
});
