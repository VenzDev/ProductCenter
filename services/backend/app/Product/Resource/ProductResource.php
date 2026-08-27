<?php

declare(strict_types=1);

namespace App\Product\Resource;

use App\Enums\AttributeType;
use App\Http\Resources\Concerns\HasRequestedIncludes;
use App\Images\Support\ImageUrlResolver;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductImage;
use App\Product\Support\ProductImageGalleryPaths;
use App\Product\Support\ProductImagePaths;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    use HasRequestedIncludes;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $withTranslations = in_array('translations', $this->requestedIncludes($request), true);

        return [
            'id' => $this->id,
            'category' => [
                'id' => $this->category_id,
                'name' => $this->category?->name,
            ],
            'name' => $this->name,
            'name_translations' => $this->when($withTranslations, fn () => $this->getTranslations('name')),
            'description' => $this->description,
            'description_translations' => $this->when($withTranslations, fn () => $this->getTranslations('description')),
            'price_cents' => $this->price_cents,
            'currency' => $this->currency,
            'attributes' => $this->resolveAttributes(),
            'main_image' => $this->main_image
                ? ImageUrlResolver::resolve(ProductImagePaths::class, $this->id)
                : null,
            'gallery' => $this->whenLoaded('images',
                fn () => $this->images->map(fn (ProductImage $image) => ImageUrlResolver::resolve(
                    ProductImageGalleryPaths::class, $image->id
                ))),
        ];
    }

    /**
     * Resolves the product's raw {key: value} attributes JSONB into display-ready rows,
     * with the attribute name and select/multiselect option labels translated to the
     * current app locale (set from the Accept-Language header, see SetLocaleFromHeader).
     *
     * @return array<int, array{key: string, name: string, value: mixed, value_label: mixed}>
     */
    private function resolveAttributes(): array
    {
        $definitions = self::attributeDefinitions();

        /** @var array<string, mixed> $rawAttributes */
        $rawAttributes = $this->attributes ?? [];

        return collect($rawAttributes)
            ->map(function (mixed $value, string $key) use ($definitions) {
                $attribute = $definitions->get($key);

                if ($attribute?->type === AttributeType::TextTranslatable && is_array($value)) {
                    $value = self::translatedText($value);
                }

                return [
                    'key' => $key,
                    'name' => $attribute !== null ? $attribute->name : $key,
                    'value' => $value,
                    'value_label' => self::valueLabel($attribute, $value),
                ];
            })
            ->values()
            ->all();
    }

    private static function valueLabel(?Attribute $attribute, mixed $value): mixed
    {
        if (! $attribute?->type->hasOptions()) {
            return $value;
        }

        $options = $attribute->translatedOptions();

        if (is_array($value)) {
            return collect($value)->map(fn ($option) => $options[$option] ?? $option)->all();
        }

        return $options[$value] ?? $value;
    }

    /**
     * @param  array<string, string|null>  $value
     */
    private static function translatedText(array $value): ?string
    {
        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale');

        return $value[$locale] ?? $value[$fallback] ?? null;
    }

    /**
     * Loaded once per request regardless of how many products this resource formats.
     *
     * @return Collection<string, Attribute>
     */
    private static function attributeDefinitions(): Collection
    {
        return once(fn () => Attribute::query()->get()->keyBy('key')->collect());
    }
}
