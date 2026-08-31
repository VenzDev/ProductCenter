<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Product\Action\CreateProduct as CreateProductAction;
use App\Product\ObjectValue\NewProduct;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected CreateProductAction $createProduct;

    public function boot(CreateProductAction $createProduct): void
    {
        $this->createProduct = $createProduct;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Product
    {
        /** @var array<string, string> $name */
        $name = $data['name'];
        /** @var array<string, string> $description */
        $description = $data['description'] ?? [];
        /** @var array<string, mixed> $attributes */
        $attributes = $data['attributes'] ?? [];

        return $this->createProduct->handle(new NewProduct(
            categoryId: (int) $data['category_id'],
            name: $name,
            priceCents: (int) $data['price_cents'],
            mainImage: (string) $data['main_image'],
            currency: (string) $data['currency'],
            attributes: $attributes,
            description: $description,
        ));
    }
}
