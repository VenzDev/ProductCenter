<?php

declare(strict_types=1);

namespace App\Product\Resource;

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
        $locale = app()->getLocale();

        return [
            'key' => $this->key,
            'name' => $this->resolvedName($locale),
            'value' => $this->resolvedValue($locale),
            'value_label' => $this->resolvedValueLabel($locale),
        ];
    }
}
