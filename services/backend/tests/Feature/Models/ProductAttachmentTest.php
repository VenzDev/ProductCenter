<?php

declare(strict_types=1);

use App\Ai\AttachmentEmbeddingsGeneration\Job\GenerateAttachmentEmbeddingsJob;
use Illuminate\Support\Facades\Bus;
use Tests\Factories\ProductFactory;

test('creating a pdf attachment dispatches the embedding job', function () {
    Bus::fake([GenerateAttachmentEmbeddingsJob::class]);

    $product = ProductFactory::new()->createQuietly();

    $attachment = $product->attachments()->create([
        'path' => 'products/attachments/manual.pdf',
        'label' => 'Manual',
    ]);

    Bus::assertDispatched(
        GenerateAttachmentEmbeddingsJob::class,
        fn (GenerateAttachmentEmbeddingsJob $job) => $job->attachmentId === $attachment->id,
    );
});

test('creating a non-pdf attachment does not dispatch the embedding job', function () {
    Bus::fake([GenerateAttachmentEmbeddingsJob::class]);

    $product = ProductFactory::new()->createQuietly();

    $product->attachments()->create([
        'path' => 'products/attachments/warranty.jpg',
        'label' => 'Warranty card photo',
    ]);

    Bus::assertNotDispatched(GenerateAttachmentEmbeddingsJob::class);
});
