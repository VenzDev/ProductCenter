<?php

declare(strict_types=1);

use App\Ai\AttachmentEmbeddingsGeneration\Job\GenerateAttachmentEmbeddingsJob;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\AttachmentsRelationManager;
use App\Storage\StorageDisk;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Factories\AdminFactory;
use Tests\Factories\ProductFactory;

test('an admin can upload a product attachment to the s3 disk', function () {
    Storage::fake(StorageDisk::S3);
    Bus::fake([GenerateAttachmentEmbeddingsJob::class]);

    $admin = AdminFactory::new()->create();
    $product = ProductFactory::new()->createQuietly();
    $this->actingAs($admin, 'admin');
    $file = UploadedFile::fake()->create('manual.pdf');

    Livewire::test(AttachmentsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callAction(TestAction::make('create')->table(), data: [
            'path' => $file,
            'label' => 'Manual',
        ])
        ->assertHasNoFormErrors();

    $attachment = $product->attachments()->first();
    expect($attachment)->not->toBeNull()
        ->and($attachment->label)->toBe('Manual')
        ->and($attachment->path)->toStartWith('products/attachments/');
    Storage::disk(StorageDisk::S3)->assertExists($attachment->path);
});

test('an admin can download a product attachment', function () {
    Storage::fake(StorageDisk::S3);
    Bus::fake([GenerateAttachmentEmbeddingsJob::class]);

    $admin = AdminFactory::new()->create();
    $product = ProductFactory::new()->createQuietly();
    $attachment = $product->attachments()->create([
        'path' => 'products/attachments/manual.pdf',
        'label' => 'Manual',
    ]);
    $this->actingAs($admin, 'admin');

    Livewire::test(AttachmentsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->assertActionExists(TestAction::make('download')->table($attachment))
        ->assertActionHasUrl(TestAction::make('download')->table($attachment), Storage::disk(StorageDisk::S3)->url($attachment->path));
});
