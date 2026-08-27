<?php

declare(strict_types=1);

namespace App\Product\ObjectValue;

use App\Enums\AttributeType;
use App\Models\Attribute;

readonly class ProductAttributeValue
{
    public function __construct(
        public string $key,
        public mixed $value,
        public ?Attribute $attribute,
    ) {}

    public function resolvedName(string $locale): string
    {
        return $this->attribute?->getTranslation('name', $locale, false)
            ?: $this->attribute?->getTranslation('name', config('app.fallback_locale'))
            ?: $this->key;
    }

    public function resolvedValue(string $locale): mixed
    {
        if ($this->attribute?->type === AttributeType::TextTranslatable && is_array($this->value)) {
            return $this->value[$locale] ?? $this->value[config('app.fallback_locale')] ?? null;
        }

        return $this->value;
    }

    public function resolvedValueLabel(string $locale): mixed
    {
        if (! $this->attribute?->type->hasOptions()) {
            return $this->resolvedValue($locale);
        }

        $options = $this->translatedOptions($this->attribute, $locale);
        $value = $this->resolvedValue($locale);

        if (is_array($value)) {
            return collect($value)->map(fn ($option) => $options[$option] ?? $option)->all();
        }

        return $options[$value] ?? $value;
    }

    /**
     * @return array<string, string> option key => label, resolved to the given locale
     */
    private function translatedOptions(Attribute $attribute, string $locale): array
    {
        $fallback = config('app.fallback_locale');

        return collect($attribute->options ?? [])
            ->mapWithKeys(fn (array $option) => [
                $option['key'] => $option['name'][$locale] ?? $option['name'][$fallback] ?? $option['key'],
            ])
            ->all();
    }
}
