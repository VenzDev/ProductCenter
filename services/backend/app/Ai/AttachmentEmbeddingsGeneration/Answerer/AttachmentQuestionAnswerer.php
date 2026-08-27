<?php

declare(strict_types=1);

namespace App\Ai\AttachmentEmbeddingsGeneration\Answerer;

use App\Ai\AttachmentEmbeddingsGeneration\Embedder\ProductAttachmentEmbedderInterface;
use App\Models\Product;
use App\Models\ProductAttachmentChunk;
use Illuminate\Support\Collection;
use Pgvector\Laravel\Distance;
use Pgvector\Laravel\Vector;

class AttachmentQuestionAnswerer
{
    private const CANDIDATE_CHUNKS = 20;

    private const ANSWER_CHUNKS = 5;

    public function __construct(private readonly ProductAttachmentEmbedderInterface $ai) {}

    public function answer(Product $product, string $question): AttachmentAnswer
    {
        $questionEmbedding = $this->ai->embed([$question])[0];

        $candidates = ProductAttachmentChunk::query()
            ->whereHas('attachment', fn ($query) => $query->where('product_id', $product->id))
            ->nearestNeighbors('embedding', new Vector($questionEmbedding), Distance::Cosine)
            ->limit(self::CANDIDATE_CHUNKS)
            ->get();

        if ($candidates->isEmpty()) {
            return new AttachmentAnswer('No manual is available for this product yet.', []);
        }

        $chunks = $this->selectChunks($question, $candidates);

        $context = $chunks
            ->map(fn (ProductAttachmentChunk $chunk, int $i) => "[{$i}] {$chunk->content}")
            ->implode("\n\n");

        $answer = $this->ai->answerQuestion($question, $context);

        return new AttachmentAnswer(
            $answer,
            $chunks
                ->map(fn (ProductAttachmentChunk $chunk) => [
                    'attachment_id' => $chunk->product_attachment_id,
                    'chunk_index' => $chunk->chunk_index,
                ])
                ->all(),
        );
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
