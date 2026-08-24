<?php

declare(strict_types=1);

use App\Models\Category;

test('a root category gets a slug derived from its name', function () {
    $category = Category::create(['name' => 'Laptops']);

    expect($category->slug)->toBe('laptops');
});

test('a child category gets a slug prefixed with its parent\'s slug', function () {
    $parent = Category::create(['name' => 'Laptops']);
    $child = Category::create(['name' => 'Pro Gaming', 'parent_id' => $parent->id]);

    expect($child->slug)->toBe('laptops/pro-gaming');
});

test('any manually supplied slug is overwritten', function () {
    $category = Category::create(['name' => 'Laptops', 'slug' => 'something-else']);

    expect($category->slug)->toBe('laptops');
});

test('renaming a root category cascades the new slug to its children', function () {
    $parent = Category::create(['name' => 'Laptops']);
    $child = Category::create(['name' => 'Pro Gaming', 'parent_id' => $parent->id]);

    $parent->update(['name' => 'Notebooks']);
    $child->refresh();

    expect($parent->slug)->toBe('notebooks');
    expect($child->slug)->toBe('notebooks/pro-gaming');
});

test('reparenting a category re-derives its slug', function () {
    $laptops = Category::create(['name' => 'Laptops']);
    $phones = Category::create(['name' => 'Phones']);
    $accessories = Category::create(['name' => 'Accessories', 'parent_id' => $laptops->id]);

    $accessories->update(['parent_id' => $phones->id]);

    expect($accessories->slug)->toBe('phones/accessories');
});
