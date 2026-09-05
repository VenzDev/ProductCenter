<?php

declare(strict_types=1);

use App\Images\Jobs\GenerateWebpImageJob;
use App\Images\Jobs\RelocateUploadedAssetJob;
use App\Images\Support\AssetPathResolver;
use App\Models\Asset;
use App\Storage\StorageDisk;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Tests\Factories\ProductFactory;

function fakeJpegBytes(): string
{
    return (string) (new ImageManager(new Driver))->create(20, 20)->toJpeg();
}

function createStagedMainImageAsset(string $stagingKey, ?string $bytes = null): Asset
{
    Storage::disk(StorageDisk::S3)->put($stagingKey, $bytes ?? fakeJpegBytes());
    $product = ProductFactory::new()->createQuietly();

    // withoutEvents avoids the real AssetObserver dispatch — these tests drive the job directly.
    return Asset::withoutEvents(fn () => $product->mainImage()->create(['path' => $stagingKey]));
}

function relocateAsset(Asset $asset): void
{
    // GenerateWebpImageJob::dispatch() inside the job runs synchronously here (sync
    // queue driver in tests), so both variants exist by the time this call returns.
    (new RelocateUploadedAssetJob($asset->id))->handle();
}

test('handling the job relocates a staged upload and generates both webp variants', function () {
    Storage::fake(StorageDisk::S3);
    $asset = createStagedMainImageAsset('product-images/tmp/abc123.jpg');

    relocateAsset($asset);

    $fresh = $asset->fresh();
    expect($fresh->path)->toBe("product-images/{$asset->owner_id}/main-image.jpg");
    expect($fresh->sha256)->not->toBeNull();

    Storage::disk(StorageDisk::S3)->assertMissing('product-images/tmp/abc123.jpg');
    Storage::disk(StorageDisk::S3)->assertExists($fresh->path);
    Storage::disk(StorageDisk::S3)->assertExists(AssetPathResolver::webp($fresh));
    Storage::disk(StorageDisk::S3)->assertExists(AssetPathResolver::thumbnailWebp($fresh));
});

test('replacing the image removes the stale canonical original when the extension changes', function () {
    Storage::fake(StorageDisk::S3);
    $asset = createStagedMainImageAsset('product-images/tmp/first.jpg');
    relocateAsset($asset);

    $fresh = $asset->fresh();
    Storage::disk(StorageDisk::S3)->put("product-images/{$fresh->owner_id}/uploads/second.png", fakeJpegBytes());
    Asset::withoutEvents(fn () => $fresh->update(['path' => "product-images/{$fresh->owner_id}/uploads/second.png"]));

    relocateAsset($fresh->fresh());

    $fresh = $asset->fresh();
    expect($fresh->path)->toBe("product-images/{$fresh->owner_id}/main-image.png");
    Storage::disk(StorageDisk::S3)->assertMissing("product-images/{$fresh->owner_id}/main-image.jpg");
    Storage::disk(StorageDisk::S3)->assertExists("product-images/{$fresh->owner_id}/main-image.png");
});

test('staging byte-identical content again skips the move and webp regeneration', function () {
    Storage::fake(StorageDisk::S3);
    $bytes = fakeJpegBytes();
    $asset = createStagedMainImageAsset('product-images/tmp/first.jpg', $bytes);
    relocateAsset($asset);
    $canonicalPath = $asset->fresh()->path;

    // Same bytes, staged again under a different Filament tmp name (e.g. the same file re-picked).
    $duplicateStagingKey = "product-images/{$asset->owner_id}/uploads/duplicate.jpg";
    Storage::disk(StorageDisk::S3)->put($duplicateStagingKey, $bytes);
    Asset::withoutEvents(fn () => $asset->fresh()->update(['path' => $duplicateStagingKey]));

    Bus::fake([GenerateWebpImageJob::class]);
    relocateAsset($asset->fresh());

    $fresh = $asset->fresh();
    expect($fresh->path)->toBe($canonicalPath);
    Storage::disk(StorageDisk::S3)->assertMissing($duplicateStagingKey);
    Storage::disk(StorageDisk::S3)->assertExists($canonicalPath, $bytes);
    Bus::assertNotDispatched(GenerateWebpImageJob::class);
});

test('a job for an asset that no longer exists does nothing without throwing', function () {
    expect(fn () => (new RelocateUploadedAssetJob(999999))->handle())
        ->not->toThrow(Throwable::class);
});
