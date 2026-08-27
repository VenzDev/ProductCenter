<?php

declare(strict_types=1);

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\ImagesRelationManager;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('an admin can upload a gallery image to the s3 disk', function () {
    Storage::fake('s3');

    $admin = Admin::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
    ]);
    $this->actingAs($admin, 'admin');
    $image = UploadedFile::fake()->image('photo.jpg');

    Livewire::test(ImagesRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('create', data: [
            'path' => $image,
        ])
        ->assertHasNoTableActionErrors();

    $galleryImage = $product->images()->first();
    expect($galleryImage)->not->toBeNull();
    expect($galleryImage->path)->toBe("product-images/gallery/{$galleryImage->id}/image.jpg");
    Storage::disk('s3')->assertExists($galleryImage->path);
});

test('an admin can delete a gallery image', function () {
    Storage::fake('s3');

    $admin = Admin::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
    ]);
    // saveQuietly avoids the real ProductGalleryObserver dispatch — this test only
    // cares about the delete action, not relocation/webp generation.
    $galleryImage = new ProductImage(['product_id' => $product->id, 'path' => 'product-images/gallery/1/image.jpg']);
    $galleryImage->saveQuietly();
    $this->actingAs($admin, 'admin');

    Livewire::test(ImagesRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('delete', record: $galleryImage);

    expect($product->images()->count())->toBe(0);
});
