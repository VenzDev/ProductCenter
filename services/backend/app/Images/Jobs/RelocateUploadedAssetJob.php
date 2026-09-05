<?php

declare(strict_types=1);

namespace App\Images\Jobs;

use App\Images\Support\AssetPathResolver;
use App\Images\Support\StaleOriginalCleaner;
use App\Models\Asset;
use App\Storage\StorageDisk;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

// Normalizes wherever Filament staged an uploaded image (product-images/tmp/..., or
// .../{id}/uploads/... on replacement) into the asset's canonical original path, then hands
// off to GenerateWebpImageJob, which only cares about that one finished path and knows
// nothing about assets or staging. If the staged file's content hash matches what's already
// published, the upload is a byte-identical re-stage (e.g. the same file picked twice under
// different Filament tmp names) — skip the move and webp regeneration entirely.
class RelocateUploadedAssetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $assetId,
    ) {}

    public function handle(): void
    {
        $asset = Asset::find($this->assetId);

        if (! $asset) {
            Log::info("RelocateUploadedAssetJob: asset [{$this->assetId}] no longer exists, skipping");

            return;
        }

        $stagedPath = $asset->path;

        if (! $stagedPath) {
            return;
        }

        $disk = Storage::disk(StorageDisk::S3);
        $canonicalPath = AssetPathResolver::original($asset, pathinfo($stagedPath, PATHINFO_EXTENSION));
        $hash = hash('sha256', (string) $disk->get($stagedPath));
        $isDuplicateOfPublished = $hash === $asset->sha256 && $disk->exists($canonicalPath);

        if ($isDuplicateOfPublished) {
            if ($stagedPath !== $canonicalPath) {
                $disk->delete($stagedPath);
            }
        } elseif ($stagedPath !== $canonicalPath) {
            StaleOriginalCleaner::deleteSameStem($disk, $canonicalPath);
            $disk->move($stagedPath, $canonicalPath);
        }

        $asset->path = $canonicalPath;
        $asset->sha256 = $hash;
        $asset->saveQuietly();

        if (! $isDuplicateOfPublished) {
            GenerateWebpImageJob::dispatch($canonicalPath);
        }
    }
}
