<?php

namespace App\Services\Sqs\Data;

readonly class ProductDescriptionGeneratedData
{
    public function __construct(
        public int $productId,
        public string $locale,
        public string $description,
    ) {}

    // Only call after the raw message has passed contract validation — this trusts the
    // decoded object's shape rather than re-checking it.
    /**
     * @param  object{product_id: int, locale: string, description: string}  $decoded
     */
    public static function fromValidated(object $decoded): self
    {
        return new self(
            productId: $decoded->product_id,
            locale: $decoded->locale,
            description: $decoded->description,
        );
    }
}
