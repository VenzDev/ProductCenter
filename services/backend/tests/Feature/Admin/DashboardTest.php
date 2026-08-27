<?php

declare(strict_types=1);

use Tests\Factories\AdminFactory;

test('an unauthenticated request to the admin page is redirected to microsoft login', function () {
    $response = $this->get('/admin');

    $response->assertRedirect('/auth/microsoft/redirect');
});

test('a logged in admin can access the admin page', function () {
    $admin = AdminFactory::new()->create();

    $response = $this->actingAs($admin, 'admin')->get('/admin');

    $response->assertOk();
});

test('an admin can log out', function () {
    $admin = AdminFactory::new()->create();

    $response = $this->actingAs($admin, 'admin')->post('/admin/logout');

    $response->assertRedirect('/');
    $this->assertGuest('admin');
});
