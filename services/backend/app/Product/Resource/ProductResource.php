<?php

declare(strict_types=1);

namespace App\Product\Resource;

use App\Http\Resources\Concerns\HasRequestedIncludes;
use App\Images\Support\ImageUrlResolver;
use App\Models\Product;
use App\Models\ProductImage;
use App\Product\Support\ProductImageGalleryPaths;
use App\Product\Support\ProductImagePaths;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'attributes' => $this->attributes,
            'main_image' => $this->main_image
                ? ImageUrlResolver::resolve(ProductImagePaths::class, $this->id)
                : null,
            'gallery' => $this->whenLoaded('images',
                fn () => $this->images->map(fn (ProductImage $image) => ImageUrlResolver::resolve(
                    ProductImageGalleryPaths::class, $image->id
                ))),
        ];
    }
}
