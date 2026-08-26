<?php

declare(strict_types=1);

namespace App\Images\Jobs;

use App\Images\Contracts\HasImagePaths;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

// Normalizes wherever Filament staged an uploaded image (product-images/tmp/...,
// or .../{id}/uploads/... on replacement) into the model's canonical original
// path, then hands off to GenerateWebpImageJob, which only cares about that one
// finished path and knows nothing about models, columns, or staging.
class RelocateUploadedImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  class-string<Model>  $modelClass
     * @param  class-string<HasImagePaths>  $imagePathsClass
     */
    public function __construct(
        public readonly string $modelClass,
        public readonly int $modelId,
        public readonly string $imageColumn,
        public readonly string $imagePathsClass,
    ) {}

    public function handle(): void
    {
        $model = $this->modelClass::find($this->modelId);

        if (! $model) {
            Log::info("RelocateUploadedImageJob: {$this->modelClass} [{$this->modelId}] no longer exists, skipping");

            return;
        }

        $currentPath = $model->getAttribute($this->imageColumn);

        if (! $currentPath) {
            return;
        }

        $disk = Storage::disk('s3');
        $paths = $this->imagePathsClass;
        $canonicalPath = $paths::original($this->modelId, pathinfo($currentPath, PATHINFO_EXTENSION));

        if ($currentPath !== $canonicalPath) {
            $this->deleteStaleOriginal($disk, $canonicalPath);
            $disk->move($currentPath, $canonicalPath);
            $model->setAttribute($this->imageColumn, $canonicalPath);
            $model->saveQuietly();
        }

        GenerateWebpImageJob::dispatch($canonicalPath);
    }

    private function deleteStaleOriginal(Filesystem $disk, string $canonicalPath): void
    {
        $directory = dirname($canonicalPath);
        $stem = pathinfo($canonicalPath, PATHINFO_FILENAME);

        foreach ($disk->files($directory) as $existing) {
            if (pathinfo($existing, PATHINFO_FILENAME) === $stem) {
                $disk->delete($existing);
            }
        }
    }
}
