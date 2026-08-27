<?php

declare(strict_types=1);

namespace App\Ai\DescriptionGeneration\Job;

use App\Ai\DescriptionGeneration\Formatter\ProductAttributeFormatter;
use App\Ai\DescriptionGeneration\Generator\ProductDescriptionGeneratorInterface;
use App\Ai\DescriptionGeneration\Resolver\ProductImageResolver;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateProductDescriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $productId,
        public readonly string $locale,
    ) {}

    public function handle(
        ProductDescriptionGeneratorInterface $ai,
        ProductAttributeFormatter $attributeFormatter,
        ProductImageResolver $imageResolver,
    ): void {
        $product = Product::find($this->productId);

        if (! $product) {
            Log::info("GenerateProductDescriptionJob: product [{$this->productId}] no longer exists, skipping");

            return;
        }

        $attributes = $attributeFormatter->format($product, $this->locale);

        $description = $ai->generate(
            $this->getName($product),
            $attributes,
            $imageResolver->resolve($product),
            $this->locale
        );

        $product->setTranslation('description', $this->locale, $description);
        $product->save();
    }

    private function getName(Product $product): string
    {
        return $product->getTranslation('name', $this->locale, false)
            ?: $product->getTranslation('name', config('app.fallback_locale'));
    }
}
