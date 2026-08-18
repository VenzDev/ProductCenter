<?php

use App\Ai\Jobs\GenerateAttachmentEmbeddingsJob;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\AttachmentsRelationManager;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('an admin can upload a product attachment to the s3 disk', function () {
    Storage::fake('s3');
    // Embedding generation is exercised in GenerateAttachmentEmbeddingsJobTest and
    // ProductAttachmentTest — faked here so this test only asserts the upload itself.
    Bus::fake([GenerateAttachmentEmbeddingsJob::class]);

    $admin = Admin::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
    ]);
    $this->actingAs($admin, 'admin');
    $file = UploadedFile::fake()->create('manual.pdf');

    Livewire::test(AttachmentsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('create', data: [
            'path' => $file,
            'label' => 'Manual',
        ])
        ->assertHasNoTableActionErrors();

    $attachment = $product->attachments()->first();
    expect($attachment)->not->toBeNull();
    expect($attachment->label)->toBe('Manual');
    expect($attachment->path)->toStartWith('products/attachments/');
    Storage::disk('s3')->assertExists($attachment->path);
});

test('an admin can download a product attachment', function () {
    Storage::fake('s3');
    Bus::fake([GenerateAttachmentEmbeddingsJob::class]);

    $admin = Admin::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
    ]);
    $attachment = $product->attachments()->create([
        'path' => 'products/attachments/manual.pdf',
        'label' => 'Manual',
    ]);
    $this->actingAs($admin, 'admin');

    Livewire::test(AttachmentsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->assertTableActionExists('download', record: $attachment)
        ->assertTableActionHasUrl('download', Storage::disk('s3')->url($attachment->path), record: $attachment);
});
