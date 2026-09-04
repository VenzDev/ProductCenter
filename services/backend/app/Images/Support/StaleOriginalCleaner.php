<?php

declare(strict_types=1);

namespace App\Images\Support;

use Illuminate\Contracts\Filesystem\Filesystem;

// Deletes any file already sitting at the same "stem" (same directory + filename,
// any extension) as the given canonical path — used before writing a new original
// image so a format change (e.g. a replacement .png where a .jpg used to be)
// doesn't leave the old file behind.
class StaleOriginalCleaner
{
    public static function deleteSameStem(Filesystem $disk, string $canonicalPath): void
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
