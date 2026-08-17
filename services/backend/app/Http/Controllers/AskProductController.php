<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductAttachmentChunk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pgvector\Laravel\Distance;
use Pgvector\Laravel\Vector;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;

class AskProductController extends Controller
{
    private const CHUNKS_PER_ANSWER = 8;

    public function __invoke(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
        ]);

        $embeddings = Prism::embeddings()
            ->using(Provider::OpenAI, 'text-embedding-3-small')
            ->fromInput($data['question'])
            ->asEmbeddings();

        $chunks = ProductAttachmentChunk::query()
            ->whereHas('attachment', fn ($query) => $query->where('product_id', $product->id))
            ->nearestNeighbors('embedding', new Vector($embeddings->embeddings[0]->embedding), Distance::Cosine)
            ->limit(self::CHUNKS_PER_ANSWER)
            ->get();

        if ($chunks->isEmpty()) {
            return response()->json([
                'answer' => 'No manual is available for this product yet.',
                'sources' => [],
            ]);
        }

        $context = $chunks
            ->map(fn (ProductAttachmentChunk $chunk, int $i) => "[{$i}] {$chunk->content}")
            ->implode("\n\n");

        $response = Prism::text()
            ->using(Provider::OpenAI, 'gpt-4o-mini')
            ->withSystemPrompt(
                "Answer the user's question using only the numbered manual excerpts below. ".
                "If the excerpts don't contain the answer, say so instead of guessing.\n\n{$context}"
            )
            ->withPrompt($data['question'])
            ->asText();

        return response()->json([
            'answer' => $response->text,
            'sources' => $chunks
                ->map(fn (ProductAttachmentChunk $chunk) => [
                    'attachment_id' => $chunk->product_attachment_id,
                    'chunk_index' => $chunk->chunk_index,
                ])
                ->all(),
        ]);
    }
}
