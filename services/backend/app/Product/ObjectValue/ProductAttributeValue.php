<?php

declare(strict_types=1);

namespace App\Product\ObjectValue;

use App\Models\Attribute;

readonly class ProductAttributeValue
{
    public function __construct(
        public string $key,
        public mixed $value,
        public ?Attribute $attribute,
    ) {}
}
