<?php

namespace App\Services\Sqs\Data;

use App\Models\Product;
use JsonSerializable;

readonly class ProductDescriptionRequestData implements JsonSerializable
{
    /**
     * @param  array<string, string>  $name
     * @param  array<string, mixed>|null  $attributes
     */
    public function __construct(
        public int $productId,
        public string $locale,
        public array $name,
        public ?array $attributes,
    ) {}

    public static function fromProduct(Product $product, string $locale): self
    {
        return new self(
            productId: $product->id,
            locale: $locale,
            name: $product->getTranslations('name'),
            attributes: $product->getAttribute('attributes'),
        );
    }

    /**
     * @return array{product_id: int, locale: string, name: array<string, string>, attributes: array<string, mixed>|null}
     */
    public function jsonSerialize(): array
    {
        return [
            'product_id' => $this->productId,
            'locale' => $this->locale,
            'name' => $this->name,
            'attributes' => $this->attributes,
        ];
    }
}
