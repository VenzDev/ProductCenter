<?php

use App\Models\Category;
use App\Models\Product;
use Pgvector\Laravel\Vector;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\EmbeddingsResponseFake;
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
        TextResponseFake::make()->withText('Remove the transport screws first.'),
    ]);
    $product = createProductWithManual();

    $response = $this->postJson("/api/products/{$product->id}/ask", [
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

    $response = $this->postJson("/api/products/{$product->id}/ask", [
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

    $response = $this->postJson("/api/products/{$product->id}/ask", []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('question');
});
