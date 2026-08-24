<?php

declare(strict_types=1);

namespace App\Category\Observers;

use App\Category\Support\CategorySlugger;
use App\Models\Category;
use Illuminate\Support\Str;
use LogicException;

class CategoryObserver
{
    public function saving(Category $category): void
    {
        $this->updateSlug($category);
    }

    public function saved(Category $category): void
    {
        $this->updateChildrenSlug($category);
    }

    private function updateSlug(Category $category): void
    {
        $parent = Category::query()->find($category->parent_id) ?? null;
        $this->validateCategoryLevel($parent);

        $name = $category->getTranslation('name', config('app.fallback_locale'));

        if ($category->isRoot()) {
            $category->slug = CategorySlugger::slug($name);

            return;
        }

        if ($parent) {
            $category->slug = CategorySlugger::slug($name, $parent->slug);
        }
    }

    private function updateChildrenSlug(Category $category): void
    {
        if (! $category->wasChanged('slug')) {
            return;
        }

        foreach ($category->children as $child) {
            /** @var Category $child */
            $ownSlug = Str::afterLast($child->slug, '/');

            Category::query()
                ->whereKey($child->id)
                ->update(['slug' => "{$category->slug}/{$ownSlug}"]);
        }
    }

    private function validateCategoryLevel(?Category $parent): void
    {
        if ($parent && ! $parent->isRoot()) {
            throw new LogicException('Categories only support two levels: a subcategory cannot itself have subcategories.');
        }
    }
}
