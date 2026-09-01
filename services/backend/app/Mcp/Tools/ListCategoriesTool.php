<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Category;
use Illuminate\Support\Collection;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list-categories')]
#[IsReadOnly]
#[Description(
    'Lists the leaf categories products can be filed under (categories that have no sub-categories). '.
    'Returns each one as "#id  Parent / Child" so you can pass its id as category_id to create-product.'
)]
class ListCategoriesTool extends Tool
{
    public function handle(): Response
    {
        $categories = Category::query()->get();
        $parentIds = $categories->pluck('parent_id')->flip();
        $locale = (string) config('app.fallback_locale');

        $lines = $categories
            ->reject(fn (Category $category): bool => $parentIds->has($category->id))
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'path' => $this->path($category, $categories, $locale),
            ])
            ->sortBy('path')
            ->map(fn (array $category): string => "#{$category['id']}\t{$category['path']}")
            ->values();

        if ($lines->isEmpty()) {
            return Response::text('No categories exist yet. Create some in the admin panel before adding products.');
        }

        return Response::text(
            "Leaf categories (pass the id as create-product's category_id):\n\n".$lines->implode("\n")
        );
    }

    /**
     * Full "Parent / Child" name of a category, resolved from the already-loaded set.
     *
     * @param  Collection<int, Category>  $all
     */
    private function path(Category $category, Collection $all, string $locale): string
    {
        $name = $category->getTranslation('name', $locale);
        $parent = $all->firstWhere('id', $category->parent_id);

        return $parent === null
            ? $name
            : $this->path($parent, $all, $locale).' / '.$name;
    }
}
