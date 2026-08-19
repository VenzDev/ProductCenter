<?php

use App\Ai\Jobs\GenerateAttachmentEmbeddingsJob;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Pgvector\Laravel\Vector;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\EmbeddingsResponseFake;
use Prism\Prism\ValueObjects\Embedding;

test('handling the job extracts pdf text and stores an embedding per chunk', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put(
        'products/attachments/manual.pdf',
        file_get_contents(__DIR__.'/../../Fixtures/sample-manual.pdf'),
    );
    Prism::fake([
        EmbeddingsResponseFake::make()->withEmbeddings([
            Embedding::fromArray(array_fill(0, 1536, 0.1)),
        ]),
    ]);
    // The created-hook would otherwise dispatch (and, under the sync test queue, run) this
    // job a second time as soon as the attachment below is created.
    Bus::fake([GenerateAttachmentEmbeddingsJob::class]);

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

    app()->call([new GenerateAttachmentEmbeddingsJob($attachment->id), 'handle']);

    $chunks = $attachment->chunks()->orderBy('chunk_index')->get();
    expect($chunks)->toHaveCount(1);
    expect($chunks->first()->content)->toBe('Charge the widget for 4 hours before first use.');
    expect($chunks->first()->embedding->toArray())->toHaveCount(1536);
});

test('a job for an attachment that no longer exists does nothing without throwing', function () {
    expect(fn () => app()->call([new GenerateAttachmentEmbeddingsJob(999999), 'handle']))
        ->not->toThrow(Throwable::class);
});

test('a job for an attachment that already has embeddings skips re-processing', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put(
        'products/attachments/manual.pdf',
        file_get_contents(__DIR__.'/../../Fixtures/sample-manual.pdf'),
    );
    // No fake embeddings response is queued, so a call to Prism::embed() here would throw —
    // asserting the job returns before spending any tokens on an already-embedded attachment.
    Prism::fake([]);
    Bus::fake([GenerateAttachmentEmbeddingsJob::class]);

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
    $attachment->chunks()->create([
        'chunk_index' => 0,
        'content' => 'Existing chunk.',
        'embedding' => new Vector(array_fill(0, 1536, 0.1)),
    ]);

    app()->call([new GenerateAttachmentEmbeddingsJob($attachment->id), 'handle']);

    expect($attachment->chunks()->count())->toBe(1);
});
