<?php

declare(strict_types=1);

use App\Images\Jobs\GenerateWebpImageJob;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

test('handling the job generates a webp and thumbnail next to the source image', function () {
    Storage::fake('s3');
    $bytes = (string) (new ImageManager(new Driver))->create(20, 20)->toJpeg();
    Storage::disk('s3')->put('some-dir/photo.jpg', $bytes);

    (new GenerateWebpImageJob('some-dir/photo.jpg'))->handle();

    Storage::disk('s3')->assertExists('some-dir/photo.webp');
    Storage::disk('s3')->assertExists('some-dir/photo-thumbnail.webp');
    // The source file itself is untouched — this job only ever reads it.
    Storage::disk('s3')->assertExists('some-dir/photo.jpg');
});

test('a job for a path that no longer exists does nothing without throwing', function () {
    Storage::fake('s3');

    expect(fn () => (new GenerateWebpImageJob('some-dir/missing.jpg'))->handle())
        ->not->toThrow(Throwable::class);

    Storage::disk('s3')->assertMissing('some-dir/missing.webp');
});
