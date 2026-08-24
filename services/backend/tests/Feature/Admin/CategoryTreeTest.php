<?php

use App\Enums\AttributeType;
use App\Filament\Resources\Categories\Pages\CategoryTree;
use App\Models\Admin;
use App\Models\Attribute;
use App\Models\Category;
use Livewire\Livewire;

test('an admin can view the category tree page', function () {
    $admin = Admin::factory()->create();
    Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

    $response = $this->actingAs($admin, 'admin')->get('/admin/categories');

    $response->assertOk()->assertSee('Electronics');
});

test('a newly created category defaults to a root node', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

    expect($category->isRoot())->toBeTrue();
    expect($category->parent_id)->toBe(-1);
    expect($category->order)->toBe(1);
});

test('a category assigned a parent appears in that parent\'s children', function () {
    $parent = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $child = Category::create(['name' => 'Phones', 'slug' => 'phones', 'parent_id' => $parent->id]);

    expect($child->isRoot())->toBeFalse();
    expect($parent->children->pluck('id')->all())->toBe([$child->id]);
});

test('dragging a root category under another root reparents it', function () {
    $admin = Admin::factory()->create();
    $this->actingAs($admin, 'admin');

    $electronics = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $phones = Category::create(['name' => 'Phones', 'slug' => 'phones']);

    Livewire::test(CategoryTree::class)->call('updateTree', [
        [
            'id' => $electronics->id,
            'children' => [
                ['id' => $phones->id, 'children' => []],
            ],
        ],
    ]);

    $phones->refresh();
    expect($phones->parent_id)->toBe($electronics->id);
    expect($phones->order)->toBe(1);
});

test('a category cannot be nested under a category that already has a parent', function () {
    $grandparent = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $parent = Category::create(['name' => 'Phones', 'slug' => 'phones', 'parent_id' => $grandparent->id]);

    $grandchild = Category::create(['name' => 'Accessories', 'slug' => 'accessories']);

    expect(fn () => $grandchild->update(['parent_id' => $parent->id]))
        ->toThrow(LogicException::class);
});

test('an admin can create a category with translations for both locales from the tree page', function () {
    $admin = Admin::factory()->create();
    $this->actingAs($admin, 'admin');

    Livewire::test(CategoryTree::class)
        ->mountAction('create')
        ->setActionData([
            'name' => ['en' => 'Books', 'pl' => 'Książki'],
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $category = Category::where('slug', 'books')->first();
    expect($category)->not->toBeNull();
    expect($category->getTranslation('name', 'en', false))->toBe('Books');
    expect($category->getTranslation('name', 'pl', false))->toBe('Książki');
});

test('editing a category from the tree page pre-fills the name for each locale', function () {
    $admin = Admin::factory()->create();
    $category = Category::create(['name' => ['en' => 'Electronics', 'pl' => 'Elektronika'], 'slug' => 'electronics']);
    $this->actingAs($admin, 'admin');

    Livewire::test(CategoryTree::class)
        ->call('mountTreeAction', 'edit', (string) $category->getKey())
        ->assertSchemaStateSet(['name' => ['en' => 'Electronics', 'pl' => 'Elektronika']]);
});

test('an admin can update a category name per locale from the tree page', function () {
    $admin = Admin::factory()->create();
    $category = Category::create(['name' => ['en' => 'Electronics', 'pl' => 'Elektronika'], 'slug' => 'electronics']);
    $this->actingAs($admin, 'admin');

    Livewire::test(CategoryTree::class)
        ->call('mountTreeAction', 'edit', (string) $category->getKey())
        ->setActionData([
            'name' => ['en' => 'Consumer Electronics', 'pl' => 'Elektronika Użytkowa'],
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $category->refresh();
    expect($category->getTranslation('name', 'en', false))->toBe('Consumer Electronics');
    expect($category->getTranslation('name', 'pl', false))->toBe('Elektronika Użytkowa');
});

test('an admin can assign attributes to a category from the tree page', function () {
    $admin = Admin::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $weight = Attribute::create(['key' => 'weight_kg', 'name' => 'Weight', 'type' => AttributeType::Number]);
    $color = Attribute::create(['key' => 'color', 'name' => 'Color', 'type' => AttributeType::Select, 'options' => ['red', 'blue']]);
    $this->actingAs($admin, 'admin');

    Livewire::test(CategoryTree::class)
        ->call('mountTreeAction', 'edit', (string) $category->getKey())
        ->setActionData([
            'name' => ['en' => 'Electronics'],
            'attributes' => [$weight->id, $color->id],
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect($category->attributes()->pluck('attributes.id')->sort()->values()->all())
        ->toBe([$weight->id, $color->id]);
});
