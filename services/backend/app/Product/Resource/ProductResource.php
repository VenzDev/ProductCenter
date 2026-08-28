<?php

declare(strict_types=1);

namespace App\Product\Resource;

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
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => [
                'id' => $this->category_id,
                'name' => $this->category?->name,
            ],
            'name' => $this->name,
            'description' => $this->description,
            'price_cents' => $this->price_cents,
            'currency' => $this->currency,
            'attributes' => $this->resolveAttributes($request),
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
     * @return array<int, array{key: string, name: string, value: mixed, value_label: mixed}>
     */
    private function resolveAttributes(Request $request): array
    {
        return ProductAttributeResource::collection($this->getAttributeCollection()->get())->resolve($request);
    }
}
