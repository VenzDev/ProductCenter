<?php

use App\Models\Admin;

test('an unauthenticated request to the admin page is redirected to microsoft login', function () {
    $response = $this->get('/admin');

    $response->assertRedirect('/auth/microsoft/redirect');
});

test('a logged in admin can access the admin page', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')->get('/admin');

    $response->assertOk()->assertSee($admin->email);
});

test('an admin can log out', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')->post('/admin/logout');

    $response->assertRedirect('/');
    $this->assertGuest('admin');
});
