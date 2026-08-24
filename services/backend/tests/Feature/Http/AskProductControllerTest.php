<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use Pgvector\Laravel\Vector;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\EmbeddingsResponseFake;
use Prism\Prism\Testing\StructuredResponseFake;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\ValueObjects\Embedding;

function createProductWithManual(): Product
{
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Washing machine',
        'price_cents' => 199900,
        'currency' => 'PLN',
    ]);
    $attachment = $product->attachments()->create([
        'path' => 'products/attachments/manual.pdf',
        'label' => 'manual',
    ]);
    $attachment->chunks()->create([
        'chunk_index' => 0,
        'content' => 'Remove the transport screws before first use.',
        'embedding' => new Vector(array_fill(0, 1536, 0.1)),
    ]);

    return $product;
}

test('asking about a product with a manual returns an answer grounded in its chunks', function () {
    Prism::fake([
        EmbeddingsResponseFake::make()->withEmbeddings([
            Embedding::fromArray(array_fill(0, 1536, 0.1)),
        ]),
        StructuredResponseFake::make()->withStructured(['indices' => [0]]),
        TextResponseFake::make()->withText('Remove the transport screws first.'),
    ]);
    $product = createProductWithManual();

    $response = $this->postJson("/api/v1/products/{$product->id}/ask", [
        'question' => 'How do I prepare the washing machine before first use?',
    ]);

    $response->assertOk();
    $response->assertJson([
        'answer' => 'Remove the transport screws first.',
        'sources' => [
            ['attachment_id' => $product->attachments()->first()->id, 'chunk_index' => 0],
        ],
    ]);
});

test('rerank picks the excerpt the LLM found relevant even when it is not the closest vector match', function () {
    Prism::fake([
        EmbeddingsResponseFake::make()->withEmbeddings([
            Embedding::fromArray(array_fill(0, 1536, 0.1)),
        ]),
        StructuredResponseFake::make()->withStructured(['indices' => [1]]),
        TextResponseFake::make()->withText('Use the steam refresh program.'),
    ]);

    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Washing machine',
        'price_cents' => 199900,
        'currency' => 'PLN',
    ]);
    $attachment = $product->attachments()->create([
        'path' => 'products/attachments/manual.pdf',
        'label' => 'manual',
    ]);
    $attachment->chunks()->create([
        'chunk_index' => 0,
        'content' => 'Remove the transport screws before first use.',
        'embedding' => new Vector(array_fill(0, 1536, 0.1)), // identical to the question embedding: closest match
    ]);
    $attachment->chunks()->create([
        'chunk_index' => 1,
        'content' => 'Use the steam refresh program to ease ironing.',
        'embedding' => new Vector(array_fill(0, 1536, 0.2)), // further away, but what the reranker picks
    ]);

    $response = $this->postJson("/api/v1/products/{$product->id}/ask", [
        'question' => 'How do I refresh clothes with steam?',
    ]);

    $response->assertOk();
    $response->assertJson([
        'answer' => 'Use the steam refresh program.',
        'sources' => [
            ['attachment_id' => $attachment->id, 'chunk_index' => 1],
        ],
    ]);
});

test('an empty rerank selection falls back to the plain vector ranking instead of no context', function () {
    Prism::fake([
        EmbeddingsResponseFake::make()->withEmbeddings([
            Embedding::fromArray(array_fill(0, 1536, 0.1)),
        ]),
        StructuredResponseFake::make()->withStructured(['indices' => []]),
        TextResponseFake::make()->withText('No relevant info found.'),
    ]);
    $product = createProductWithManual();

    $response = $this->postJson("/api/v1/products/{$product->id}/ask", [
        'question' => 'Irrelevant question',
    ]);

    $response->assertOk();
    $response->assertJson([
        'sources' => [
            ['attachment_id' => $product->attachments()->first()->id, 'chunk_index' => 0],
        ],
    ]);
});

test('asking about a product without a manual returns a fallback answer', function () {
    Prism::fake([
        EmbeddingsResponseFake::make()->withEmbeddings([
            Embedding::fromArray(array_fill(0, 1536, 0.1)),
        ]),
    ]);
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Widget',
        'price_cents' => 1999,
        'currency' => 'PLN',
    ]);

    $response = $this->postJson("/api/v1/products/{$product->id}/ask", [
        'question' => 'How does this work?',
    ]);

    $response->assertOk();
    $response->assertJson([
        'answer' => 'No manual is available for this product yet.',
        'sources' => [],
    ]);
});

test('question is required', function () {
    $product = createProductWithManual();

    $response = $this->postJson("/api/v1/products/{$product->id}/ask", []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('question');
});
