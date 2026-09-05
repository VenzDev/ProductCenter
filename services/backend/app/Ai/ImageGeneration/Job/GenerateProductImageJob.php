<?php

declare(strict_types=1);

namespace App\Ai\ImageGeneration\Job;

use App\Ai\ImageGeneration\Generator\ProductImageGeneratorInterface;
use App\Ai\ImageGeneration\Prompt\ProductImagePromptBuilder;
use App\Images\Jobs\GenerateWebpImageJob;
use App\Images\Support\AssetPathResolver;
use App\Images\Support\StaleOriginalCleaner;
use App\Models\Product;
use App\Storage\StorageDisk;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateProductImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const string EXTENSION = 'png';

    public function __construct(
        public readonly int $productId,
    ) {}

    public function handle(
        ProductImageGeneratorInterface $ai,
        ProductImagePromptBuilder $promptBuilder,
    ): void {
        $product = Product::find($this->productId);

        if (! $product) {
            Log::info("GenerateProductImageJob: product [{$this->productId}] no longer exists, skipping");

            return;
        }

        $imageBytes = $ai->generate($promptBuilder->build($product));

        $asset = $product->mainImage ?? $product->mainImage()->make();

        $disk = Storage::disk(StorageDisk::S3);
        $canonicalPath = AssetPathResolver::original($asset, self::EXTENSION);

        StaleOriginalCleaner::deleteSameStem($disk, $canonicalPath);
        $disk->put($canonicalPath, $imageBytes, ['visibility' => 'public']);

        $asset->path = $canonicalPath;
        $asset->sha256 = hash('sha256', $imageBytes);
        $asset->saveQuietly();

        GenerateWebpImageJob::dispatch($canonicalPath);
    }
}
