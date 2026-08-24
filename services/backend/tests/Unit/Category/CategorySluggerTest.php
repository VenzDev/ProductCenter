<?php

declare(strict_types=1);

use App\Category\Support\CategorySlugger;

test('a name without a parent slug produces a plain slug', function () {
    expect(CategorySlugger::slug('Laptops'))->toBe('laptops');
});

test('a parent slug is prepended, separated by a slash', function () {
    expect(CategorySlugger::slug('Pro Gaming', 'laptops'))->toBe('laptops/pro-gaming');
});
