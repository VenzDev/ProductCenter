<?php

declare(strict_types=1);

namespace App\Images\Observers;

use App\Images\Contracts\HasImagePaths;
use App\Images\Jobs\RelocateUploadedImageJob;
use Illuminate\Database\Eloquent\Model;

abstract class WebpImageObserver
{
    abstract protected function imageColumn(): string;

    /**
     * @return class-string<HasImagePaths>
     */
    abstract protected function imagePathsClass(): string;

    public function saved(Model $model): void
    {
        $column = $this->imageColumn();
        $current = $model->getAttribute($column);

        if ($current && $current !== $model->getOriginal($column)) {
            RelocateUploadedImageJob::dispatch($model::class, (int) $model->getKey(), $column, $this->imagePathsClass());
        }
    }
}
