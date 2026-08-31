<?php

declare(strict_types=1);

namespace App\Product\ObjectValue;

readonly class NewProduct
{
    /**
     * @param  array<string, string>  $name  locale => value; must include the fallback locale
     * @param  array<string, mixed>  $attributes  attribute key => value (scalar, list, or locale map)
     * @param  array<string, string>  $description  locale => value
     */
    public function __construct(
        public int $categoryId,
        public array $name,
        public int $priceCents,
        public string $mainImage,
        public string $currency = 'PLN',
        public array $attributes = [],
        public array $description = [],
    ) {}
}
