<?php

declare(strict_types=1);

namespace App\Images\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

// Turns one image already sitting at its final path into a same-name .webp and
// a "-thumbnail.webp" sibling. Knows nothing about models, columns, or upload
// staging — see RelocateUploadedImageJob for that.
class GenerateWebpImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const THUMBNAIL_SIZE = 400;

    public function __construct(
        public readonly string $currentPath,
    ) {}

    public function handle(): void
    {
        $disk = Storage::disk('s3');

        if (! $disk->exists($this->currentPath)) {
            return;
        }

        $directory = dirname($this->currentPath);
        $stem = pathinfo($this->currentPath, PATHINFO_FILENAME);

        $original = $disk->get($this->currentPath);
        $manager = new ImageManager(new Driver);

        $webp = $manager->read($original)->toWebp(quality: 85);
        $disk->put("{$directory}/{$stem}.webp", (string) $webp, ['visibility' => 'public']);

        $thumbnail = $manager->read($original)->cover(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE)->toWebp(quality: 75);
        $disk->put("{$directory}/{$stem}-thumbnail.webp", (string) $thumbnail, ['visibility' => 'public']);
    }
}
