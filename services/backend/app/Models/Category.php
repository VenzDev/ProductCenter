<?php

declare(strict_types=1);

namespace App\Models;

use App\Category\Observers\CategoryObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SolutionForest\FilamentTree\Concern\ModelTree;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * @property-read string $name
 * @property int $parent_id
 * @property int $order
 * @property-read Collection<int, Category> $children
 */
// parent_id/order are listed explicitly rather than relying on ModelTree's
// initializeModelTree() auto-merge: that merge only fires if getFillable() is
// already non-empty, but Category's own initializer (from `use ModelTree`) runs
// before the base Model's initializeGuardsAttributes() populates $fillable from
// this attribute, so the auto-merge silently sees an empty array and no-ops.
#[Fillable(['name', 'slug', 'parent_id', 'order'])]
#[Translatable(['name'])]
#[ObservedBy(CategoryObserver::class)]
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

    /**
     * @return BelongsToMany<Attribute, $this>
     */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class);
    }

    public function determineTitleColumnName(): string
    {
        return 'name';
    }

    /**
     * @return list<int>
     */
    public function selfAndChildIds(): array
    {
        /** @var list<int> $childIds */
        $childIds = $this->children()->pluck('id')->all();

        return [(int) $this->id, ...$childIds];
    }
}
