<?php

declare(strict_types=1);

namespace App\Ai\DescriptionGeneration\Resolver;

use App\Models\Product;
use App\Product\Support\ProductImagePaths;
use App\Storage\StorageDisk;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\ValueObjects\Media\Image;

class ProductImageResolver
{
    public function resolve(Product $product): ?Image
    {
        if (! $product->main_image) {
            return null;
        }

        $path = ProductImagePaths::webp($product->id);

        if (! Storage::disk(StorageDisk::S3)->exists($path)) {
            Log::info("GenerateProductDescriptionJob: product [{$product->id}] main image not yet available on S3, generating description without it");

            return null;
        }

        return Image::fromStoragePath($path, diskName: StorageDisk::S3);
    }
}
