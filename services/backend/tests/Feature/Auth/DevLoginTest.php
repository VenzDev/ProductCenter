<?php

declare(strict_types=1);

use App\Auth\Admin\Controller\DevAuthController;
use App\Models\Admin;
use Tests\Factories\AdminFactory;

test('dev-login route does not exist outside local env', function () {
    // phpunit.xml pins APP_ENV=testing for the whole suite, so this also proves the
    // routes/web.php gate actually works: the route is never registered here.
    $response = $this->get('/admin/dev-login');

    $response->assertNotFound();
});

test('dev-login creates an admin when none exists yet and logs in as them', function () {
    expect(Admin::count())->toBe(0);

    $response = app(DevAuthController::class)->login();

    expect(Admin::count())->toBe(1);
    expect($response->getTargetUrl())->toEndWith('/admin');
    $this->assertAuthenticatedAs(Admin::first(), 'admin');
});

test('dev-login reuses the existing admin instead of creating another one', function () {
    $admin = AdminFactory::new()->create();

    app(DevAuthController::class)->login();

    expect(Admin::count())->toBe(1);
    $this->assertAuthenticatedAs($admin, 'admin');
});
