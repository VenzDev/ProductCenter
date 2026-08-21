<?php

namespace App\Models;

use App\Category\Support\CategorySlugger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SolutionForest\FilamentTree\Concern\ModelTree;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * @property-read string $name
 * @property int $parent_id
 * @property int $order
 */
// parent_id/order are listed explicitly rather than relying on ModelTree's
// initializeModelTree() auto-merge: that merge only fires if getFillable() is
// already non-empty, but Category's own initializer (from `use ModelTree`) runs
// before the base Model's initializeGuardsAttributes() populates $fillable from
// this attribute, so the auto-merge silently sees an empty array and no-ops.
#[Fillable(['name', 'slug', 'parent_id', 'order'])]
#[Translatable(['name'])]
class Category extends Model
{
    use HasTranslations;
    use ModelTree;

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function determineTitleColumnName(): string
    {
        return 'name';
    }

    /**
     * filament-tree's $maxDepth only blocks nesting in the browser widget; it never
     * validates depth server-side, so this guards the two-level rule at the data layer.
     * The same event also derives the slug, since it's the one place that already
     * looks up the parent for every create/update/reparent.
     */
    protected static function booted(): void
    {
        static::saving(function (self $category) {
            $name = $category->getTranslation('name', config('app.fallback_locale'));

            if ($category->parent_id === -1) {
                $category->slug = CategorySlugger::slug($name);

                return;
            }

            $parent = static::query()->find($category->parent_id);

            if ($parent && ! $parent->isRoot()) {
                throw new \LogicException('Categories only support two levels: a subcategory cannot itself have subcategories.');
            }

            if ($parent) {
                $category->slug = CategorySlugger::slug($name, $parent->slug);
            }
        });

        // A root category's slug is a prefix of its children's slugs, so renaming
        // or reparenting it must re-derive theirs too.
        static::saved(function (self $category) {
            if (! $category->wasChanged('slug')) {
                return;
            }

            $category->children->each->save();
        });
    }
}
