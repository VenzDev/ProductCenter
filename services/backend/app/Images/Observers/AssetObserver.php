<?php

declare(strict_types=1);

namespace App\Images\Observers;

use App\Images\Jobs\RelocateUploadedAssetJob;
use App\Models\Asset;

class AssetObserver
{
    public function saved(Asset $asset): void
    {
        if ($asset->path && $asset->path !== $asset->getOriginal('path')) {
            RelocateUploadedAssetJob::dispatch($asset->id);
        }
    }
}
