<?php

declare(strict_types=1);

namespace App\Category\Resource;

use App\Http\Resources\Concerns\HasRequestedIncludes;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
class CategoryResource extends JsonResource
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
            'name' => $this->name,
            'name_translations' => $this->when($withTranslations, fn () => $this->getTranslations('name')),
            'slug' => $this->slug,
            'children' => static::collection($this->whenLoaded('children')),
        ];
    }
}
