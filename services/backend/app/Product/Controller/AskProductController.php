<?php

declare(strict_types=1);

namespace App\Product\Controller;

use App\Ai\AttachmentEmbeddingsGeneration\Embedder\ProductAttachmentEmbedderInterface;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAttachmentChunk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Pgvector\Laravel\Distance;
use Pgvector\Laravel\Vector;

class AskProductController extends Controller
{
    private const CANDIDATE_CHUNKS = 20;

    private const ANSWER_CHUNKS = 5;

    public function __construct(private readonly ProductAttachmentEmbedderInterface $ai) {}

    public function __invoke(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
        ]);

        $questionEmbedding = $this->ai->embed([$data['question']])[0];

        $candidates = ProductAttachmentChunk::query()
            ->whereHas('attachment', fn ($query) => $query->where('product_id', $product->id))
            ->nearestNeighbors('embedding', new Vector($questionEmbedding), Distance::Cosine)
            ->limit(self::CANDIDATE_CHUNKS)
            ->get();

        if ($candidates->isEmpty()) {
            return response()->json([
                'answer' => 'No manual is available for this product yet.',
                'sources' => [],
            ]);
        }

        $chunks = $this->selectChunks($data['question'], $candidates);

        $context = $chunks
            ->map(fn (ProductAttachmentChunk $chunk, int $i) => "[{$i}] {$chunk->content}")
            ->implode("\n\n");

        $answer = $this->ai->answerQuestion($data['question'], $context);

        return response()->json([
            'answer' => $answer,
            'sources' => $chunks
                ->map(fn (ProductAttachmentChunk $chunk) => [
                    'attachment_id' => $chunk->product_attachment_id,
                    'chunk_index' => $chunk->chunk_index,
                ])
                ->all(),
        ]);
    }

    /**
     * @param  Collection<int, ProductAttachmentChunk>  $candidates
     * @return Collection<int, ProductAttachmentChunk>
     */
    private function selectChunks(string $question, Collection $candidates): Collection
    {
        $excerpts = $candidates->map(fn (ProductAttachmentChunk $chunk) => $chunk->content)->all();

        $indices = $this->ai->selectRelevantExcerpts($question, $excerpts, self::ANSWER_CHUNKS);
        if ($indices === []) {
            return $candidates->take(self::ANSWER_CHUNKS)->values();
        }

        $candidateChunks = $candidates->values()->all();

        return collect($indices)->map(fn (int $index) => $candidateChunks[$index])->values();
    }
}
