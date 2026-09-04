<?php

declare(strict_types=1);

use App\Ai\ImageGeneration\Job\GenerateProductImageJob;
use App\Images\Jobs\GenerateWebpImageJob;
use App\Storage\StorageDisk;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\ImageResponseFake;
use Prism\Prism\ValueObjects\GeneratedImage;
use Tests\Factories\ProductFactory;

test('handling the job stores the generated image as the product main image and queues webp conversion', function () {
    Storage::fake(StorageDisk::S3);
    Bus::fake([GenerateWebpImageJob::class]);
    Prism::fake([
        ImageResponseFake::make()->withImages([
            new GeneratedImage(base64: base64_encode('fake-png-bytes')),
        ]),
    ]);

    $product = ProductFactory::new()->createQuietly([
        'name' => ['en' => 'Widget'],
        'attributes' => ['weight_kg' => 1.2],
    ]);

    app()->call([new GenerateProductImageJob($product->id), 'handle']);

    $expectedPath = "product-images/{$product->id}/main-image.png";

    expect($product->fresh()->main_image)->toBe($expectedPath);
    Storage::disk(StorageDisk::S3)->assertExists($expectedPath, 'fake-png-bytes');
    Bus::assertDispatched(GenerateWebpImageJob::class, fn (GenerateWebpImageJob $job) => $job->currentPath === $expectedPath);
});

test('handling the job deletes a stale original with a different extension before writing the new one', function () {
    Storage::fake(StorageDisk::S3);
    Storage::disk(StorageDisk::S3)->put('product-images/1/main-image.jpg', 'old-jpg-bytes');
    Bus::fake([GenerateWebpImageJob::class]);
    Prism::fake([
        ImageResponseFake::make()->withImages([
            new GeneratedImage(base64: base64_encode('fake-png-bytes')),
        ]),
    ]);

    $product = ProductFactory::new()->createQuietly(['id' => 1]);

    app()->call([new GenerateProductImageJob($product->id), 'handle']);

    Storage::disk(StorageDisk::S3)->assertMissing('product-images/1/main-image.jpg');
    Storage::disk(StorageDisk::S3)->assertExists('product-images/1/main-image.png');
});

test('a job for a product that no longer exists does nothing without throwing', function () {
    expect(fn () => app()->call([new GenerateProductImageJob(999999), 'handle']))
        ->not->toThrow(Throwable::class);
});
