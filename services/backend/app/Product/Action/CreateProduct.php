<?php

declare(strict_types=1);

namespace App\Product\Action;

use App\Models\Product;
use App\Product\ObjectValue\NewProduct;

class CreateProduct
{
    public function handle(NewProduct $product): Product
    {
        return Product::create([
            'category_id' => $product->categoryId,
            'name' => $product->name,
            'description' => $product->description,
            'price_cents' => $product->priceCents,
            'currency' => $product->currency,
            'attributes' => $product->attributes,
            'main_image' => $product->mainImage,
        ]);
    }
}
