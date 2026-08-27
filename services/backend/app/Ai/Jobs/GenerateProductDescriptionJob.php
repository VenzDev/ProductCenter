<?php

declare(strict_types=1);

namespace App\Ai\Jobs;

use App\Ai\ProductDescription\ProductDescriptionGeneratorInterface;
use App\Models\Attribute;
use App\Models\Product;
use App\Product\ObjectValue\ProductAttributeValue;
use App\Product\Resource\ProductAttributeResource;
use App\Product\Support\ProductImagePaths;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\ValueObjects\Media\Image;

class GenerateProductDescriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $productId,
        public readonly string $locale,
    ) {}

    public function handle(ProductDescriptionGeneratorInterface $ai): void
    {
        $product = Product::find($this->productId);

        if (! $product) {
            Log::info("GenerateProductDescriptionJob: product [{$this->productId}] no longer exists, skipping");

            return;
        }

        $name = $product->getTranslation('name', $this->locale, false)
            ?: $product->getTranslation('name', config('app.fallback_locale'));

        $description = $ai->generate($name, $this->formatAttributes($product), $this->resolveMainImage($product), $this->locale);

        $product->setTranslation('description', $this->locale, $description);
        $product->save();
    }

    private function formatAttributes(Product $product): string
    {
        /** @var array<string, mixed> $rawAttributes */
        $rawAttributes = $product->attributes ?? [];

        if ($rawAttributes === []) {
            return 'None specified.';
        }

        $definitions = Attribute::query()->whereIn('key', array_keys($rawAttributes))->get()->keyBy('key');

        $values = collect($rawAttributes)
            ->map(fn (mixed $value, string $key) => new ProductAttributeValue($key, $value, $definitions->get($key)))
            ->values();

        // ProductAttributeResource resolves translations/option labels from the current
        // app locale, so it's switched to the target locale for the duration of this call.
        // try/finally guarantees the restore even if resolve() throws — queue:work reuses
        // this process across jobs, so a leaked locale would otherwise bleed into unrelated ones.
        $previousLocale = App::getLocale();
        App::setLocale($this->locale);

        try {
            /** @var array<int, array{key: string, name: string, value: mixed, value_label: mixed}> $formatted */
            $formatted = ProductAttributeResource::collection($values)->resolve(Request::create('/'));
        } finally {
            App::setLocale($previousLocale);
        }

        return collect($formatted)
            ->map(fn (array $attribute) => "{$attribute['name']}: {$this->stringifyValueLabel($attribute['value_label'])}")
            ->implode("\n");
    }

    private function stringifyValueLabel(mixed $valueLabel): string
    {
        return is_array($valueLabel) ? implode(', ', $valueLabel) : (string) $valueLabel;
    }

    private function resolveMainImage(Product $product): ?Image
    {
        if (! $product->main_image) {
            return null;
        }

        $path = ProductImagePaths::webp($product->id);

        if (! Storage::disk('s3')->exists($path)) {
            Log::info("GenerateProductDescriptionJob: product [{$this->productId}] main image not yet available on S3, generating description without it");

            return null;
        }

        return Image::fromStoragePath($path, diskName: 's3');
    }
}
