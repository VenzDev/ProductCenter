<?php

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Models\Admin;
use App\Models\Category;
use Livewire\Livewire;

test('an admin can list categories', function () {
    $admin = Admin::factory()->create();
    Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

    $response = $this->actingAs($admin, 'admin')->get('/admin/categories');

    $response->assertOk()->assertSee('Electronics');
});

test('an admin can create a category with translations for both locales', function () {
    $admin = Admin::factory()->create();
    $this->actingAs($admin, 'admin');

    Livewire::test(CreateCategory::class)
        ->fillForm([
            'name.en' => 'Books',
            'name.pl' => 'Książki',
            'slug' => 'books',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $category = Category::where('slug', 'books')->first();
    expect($category)->not->toBeNull();
    expect($category->getTranslation('name', 'en', false))->toBe('Books');
    expect($category->getTranslation('name', 'pl', false))->toBe('Książki');
});

test('editing a category pre-fills the name for each locale tab', function () {
    $admin = Admin::factory()->create();
    $category = Category::create(['name' => ['en' => 'Electronics', 'pl' => 'Elektronika'], 'slug' => 'electronics']);
    $this->actingAs($admin, 'admin');

    Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
        ->assertFormSet(['name.en' => 'Electronics', 'name.pl' => 'Elektronika']);
});

test('an admin can update a category name per locale', function () {
    $admin = Admin::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $this->actingAs($admin, 'admin');

    Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
        ->fillForm(['name.en' => 'Consumer Electronics', 'name.pl' => 'Elektronika Użytkowa'])
        ->call('save')
        ->assertHasNoFormErrors();

    $category->refresh();
    expect($category->getTranslation('name', 'en', false))->toBe('Consumer Electronics');
    expect($category->getTranslation('name', 'pl', false))->toBe('Elektronika Użytkowa');
});

test('viewing a category shows the name for both locales', function () {
    $admin = Admin::factory()->create();
    $category = Category::create(['name' => ['en' => 'Electronics', 'pl' => 'Elektronika'], 'slug' => 'electronics']);

    $response = $this->actingAs($admin, 'admin')->get("/admin/categories/{$category->id}");

    $response->assertOk()->assertSee('Electronics')->assertSee('Elektronika');
});
