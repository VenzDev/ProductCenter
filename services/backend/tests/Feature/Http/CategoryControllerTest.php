<?php

use App\Models\Category;

function createCategory(): Category
{
    return Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
}

test('categories can be listed', function () {
    $category = createCategory();

    $response = $this->getJson('/api/v1/categories');

    $response->assertOk();
    $response->assertJsonPath('data.0.id', $category->id);
    $response->assertJsonPath('data.0.name', 'Electronics');
});

test('a root category without subcategories has an empty children array', function () {
    createCategory();

    $response = $this->getJson('/api/v1/categories');

    $response->assertOk();
    $response->assertJsonPath('data.0.children', []);
});

test('a root category lists its subcategories nested under children', function () {
    $parent = createCategory();
    $child = Category::create(['name' => 'Phones', 'slug' => 'phones', 'parent_id' => $parent->id]);

    $response = $this->getJson('/api/v1/categories');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.id', $parent->id);
    $response->assertJsonPath('data.0.children.0.id', $child->id);
    $response->assertJsonPath('data.0.children.0.name', 'Phones');
});

test('subcategories are not listed as their own top-level entry', function () {
    $parent = createCategory();
    Category::create(['name' => 'Phones', 'slug' => 'phones', 'parent_id' => $parent->id]);

    $response = $this->getJson('/api/v1/categories');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

test('a single category can be retrieved', function () {
    $category = createCategory();

    $response = $this->getJson("/api/v1/categories/{$category->id}");

    $response->assertOk();
    $response->assertJson([
        'data' => [
            'id' => $category->id,
            'name' => 'Electronics',
            'slug' => 'electronics',
            'children' => [],
        ],
    ]);
});

test('retrieving a non-existent category returns 404', function () {
    $response = $this->getJson('/api/v1/categories/999');

    $response->assertNotFound();
});

test('the Accept-Language header switches the translated name', function () {
    $category = createCategory();
    $category->setTranslation('name', 'pl', 'Elektronika');
    $category->save();

    $response = $this->getJson("/api/v1/categories/{$category->id}", ['Accept-Language' => 'pl']);

    $response->assertOk();
    $response->assertJsonPath('data.name', 'Elektronika');
});

test('include=translations adds every language for the name field', function () {
    $category = createCategory();
    $category->setTranslation('name', 'pl', 'Elektronika');
    $category->save();

    $response = $this->getJson("/api/v1/categories/{$category->id}?include=translations");

    $response->assertOk();
    $response->assertJsonPath('data.name', 'Electronics');
    $response->assertJsonPath('data.name_translations', [
        'en' => 'Electronics',
        'pl' => 'Elektronika',
    ]);
});

test('translations are omitted by default', function () {
    $category = createCategory();

    $response = $this->getJson("/api/v1/categories/{$category->id}");

    $response->assertOk();
    $response->assertJsonMissingPath('data.name_translations');
});
