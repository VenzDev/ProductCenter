<?php

declare(strict_types=1);

use App\Images\Jobs\GenerateWebpImageJob;
use App\Storage\StorageDisk;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

test('handling the job generates a webp and thumbnail next to the source image', function () {
    Storage::fake(StorageDisk::S3);
    $bytes = (string) (new ImageManager(new Driver))->create(20, 20)->toJpeg();
    Storage::disk(StorageDisk::S3)->put('some-dir/photo.jpg', $bytes);

    (new GenerateWebpImageJob('some-dir/photo.jpg'))->handle();

    Storage::disk(StorageDisk::S3)->assertExists('some-dir/photo.webp');
    Storage::disk(StorageDisk::S3)->assertExists('some-dir/photo-thumbnail.webp');
    // The source file itself is untouched — this job only ever reads it.
    Storage::disk(StorageDisk::S3)->assertExists('some-dir/photo.jpg');
});

test('a job for a path that no longer exists does nothing without throwing', function () {
    Storage::fake(StorageDisk::S3);

    expect(fn () => (new GenerateWebpImageJob('some-dir/missing.jpg'))->handle())
        ->not->toThrow(Throwable::class);

    Storage::disk(StorageDisk::S3)->assertMissing('some-dir/missing.webp');
});
