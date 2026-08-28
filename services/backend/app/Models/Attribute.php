<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttributeType;
use App\Product\Observers\AttributeObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * @property-read string $name
 * @property AttributeType $type
 * @property array<int, array{key: string, name: array<string, string>}>|null $options
 * @property bool $filterable
 */
#[Fillable(['key', 'name', 'type', 'options', 'filterable'])]
#[Translatable(['name'])]
#[ObservedBy(AttributeObserver::class)]
class Attribute extends Model
{
    use HasTranslations;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AttributeType::class,
            'options' => 'array',
            'filterable' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * @param  list<int>  $categoryIds
     * @return Collection<int, static>
     */
    public static function filterableForCategories(array $categoryIds): Collection
    {
        return static::query()
            ->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $categoryIds))
            ->where('filterable', true)
            ->get();
    }

    /**
     * Resolves each option's translated name to the current app locale, falling back
     * to the app's fallback locale, then the option key itself.
     *
     * @return array<string, string> option key => label
     */
    public function translatedOptions(): array
    {
        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale');

        return collect($this->options ?? [])
            ->mapWithKeys(fn (array $option) => [
                $option['key'] => $option['name'][$locale] ?? $option['name'][$fallback] ?? $option['key'],
            ])
            ->all();
    }
}
