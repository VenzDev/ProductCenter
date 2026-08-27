<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => fn () => Category::create(['name' => 'Electronics', 'slug' => 'electronics'])->id,
            'name' => 'Widget',
            'price_cents' => 1999,
            'currency' => 'PLN',
            'main_image' => 'product-images/placeholder/main-image.jpg',
        ];
    }
}
