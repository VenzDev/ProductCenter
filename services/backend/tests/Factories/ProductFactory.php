<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\Models\Asset;
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
        ];
    }

    public function configure(): static
    {
        // Every product needs a main image (the Filament form requires one) — give it a
        // placeholder asset without going through AssetObserver, so building a product in a
        // test never depends on S3/queue behavior unless the test opts into that explicitly.
        return $this->afterCreating(function (Product $product) {
            Asset::withoutEvents(function () use ($product) {
                $product->mainImage()->create(['path' => 'product-images/placeholder/main-image.jpg']);
            });
        });
    }
}
