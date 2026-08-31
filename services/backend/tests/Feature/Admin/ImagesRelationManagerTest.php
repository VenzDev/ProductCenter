<?php

declare(strict_types=1);

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\ImagesRelationManager;
use App\Models\ProductImage;
use App\Storage\StorageDisk;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Factories\AdminFactory;
use Tests\Factories\ProductFactory;

test('an admin can upload a gallery image to the s3 disk', function () {
    Storage::fake(StorageDisk::S3);

    $admin = AdminFactory::new()->create();
    $product = ProductFactory::new()->createQuietly();
    $this->actingAs($admin, 'admin');
    $image = UploadedFile::fake()->image('photo.jpg');

    Livewire::test(ImagesRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callAction(TestAction::make('create')->table(), data: [
            'path' => $image,
        ])
        ->assertHasNoFormErrors();

    $galleryImage = $product->images()->first();
    expect($galleryImage)->not->toBeNull();
    expect($galleryImage->path)->toBe("product-images/gallery/{$galleryImage->id}/image.jpg");
    Storage::disk(StorageDisk::S3)->assertExists($galleryImage->path);
});

test('an admin can delete a gallery image', function () {
    Storage::fake(StorageDisk::S3);

    $admin = AdminFactory::new()->create();
    $product = ProductFactory::new()->createQuietly();
    // saveQuietly avoids the real ProductGalleryObserver dispatch — this test only
    // cares about the delete action, not relocation/webp generation.
    $galleryImage = new ProductImage(['product_id' => $product->id, 'path' => 'product-images/gallery/1/image.jpg']);
    $galleryImage->saveQuietly();
    $this->actingAs($admin, 'admin');

    Livewire::test(ImagesRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callAction(TestAction::make('delete')->table($galleryImage));

    expect($product->images()->count())->toBe(0);
});
