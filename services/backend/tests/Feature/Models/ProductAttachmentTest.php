<?php

declare(strict_types=1);

use App\Ai\Jobs\GenerateAttachmentEmbeddingsJob;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Bus;

test('creating a pdf attachment dispatches the embedding job', function () {
    Bus::fake([GenerateAttachmentEmbeddingsJob::class]);

    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    // withoutEvents skips ProductImageObserver's relocation dispatch — these tests
    // don't exercise the image pipeline, so main_image is just a required placeholder.
    $product = Product::withoutEvents(fn () => Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
        'main_image' => 'product-images/placeholder/main-image.jpg',
    ]));

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

    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    // withoutEvents skips ProductImageObserver's relocation dispatch — these tests
    // don't exercise the image pipeline, so main_image is just a required placeholder.
    $product = Product::withoutEvents(fn () => Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
        'main_image' => 'product-images/placeholder/main-image.jpg',
    ]));

    $product->attachments()->create([
        'path' => 'products/attachments/warranty.jpg',
        'label' => 'Warranty card photo',
    ]);

    Bus::assertNotDispatched(GenerateAttachmentEmbeddingsJob::class);
});
