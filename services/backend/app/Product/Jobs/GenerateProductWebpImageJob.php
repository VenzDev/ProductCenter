<?php

namespace App\Product\Jobs;

use App\Models\Product;
use App\Product\Support\ProductImagePaths;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class GenerateProductWebpImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const THUMBNAIL_SIZE = 400;

    public function __construct(
        public readonly int $productId,
    ) {}

    public function handle(): void
    {
        $product = Product::find($this->productId);

        if (! $product) {
            Log::info("GenerateProductWebpImageJob: product [{$this->productId}] no longer exists, skipping");

            return;
        }

        if (! $product->main_image) {
            return;
        }

        $disk = Storage::disk('s3');
        $canonicalDirectory = ProductImagePaths::directory($product->id);

        if (! Str::startsWith($product->main_image, "{$canonicalDirectory}/main-image.")) {
            $product->main_image = $this->relocateToCanonicalPath($disk, $product->main_image, $product->id);
            $product->saveQuietly();
        }

        $original = $disk->get($product->main_image);
        $manager = new ImageManager(new Driver);

        $webp = $manager->read($original)->toWebp(quality: 85);
        $disk->put(ProductImagePaths::webp($product->id), (string) $webp, ['visibility' => 'public']);

        $thumbnail = $manager->read($original)->cover(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE)->toWebp(quality: 75);
        $disk->put(ProductImagePaths::thumbnailWebp($product->id), (string) $thumbnail, ['visibility' => 'public']);
    }

    private function relocateToCanonicalPath(Filesystem $disk, string $stagingKey, int $productId): string
    {
        $canonicalDirectory = ProductImagePaths::directory($productId);

        foreach ($disk->files($canonicalDirectory) as $existing) {
            if (Str::startsWith(basename($existing), 'main-image.')) {
                $disk->delete($existing);
            }
        }

        $extension = pathinfo($stagingKey, PATHINFO_EXTENSION);
        $canonicalKey = ProductImagePaths::original($productId, $extension);

        $disk->move($stagingKey, $canonicalKey);

        return $canonicalKey;
    }
}
