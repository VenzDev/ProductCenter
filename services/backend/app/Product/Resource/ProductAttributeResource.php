<?php

declare(strict_types=1);

namespace App\Product\Resource;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Product\ObjectValue\ProductAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Formats a single product attribute, with its name and select/multiselect option
 * labels translated to the current app locale (set from the Accept-Language header,
 * see SetLocaleFromHeader).
 *
 * @mixin ProductAttributeValue
 */
class ProductAttributeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $attribute = $this->attribute;
        $value = $this->value;

        if ($attribute?->type === AttributeType::TextTranslatable && is_array($value)) {
            $value = self::translatedText($value);
        }

        return [
            'key' => $this->key,
            'name' => $attribute !== null ? $attribute->name : $this->key,
            'value' => $value,
            'value_label' => self::valueLabel($attribute, $value),
        ];
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
}
