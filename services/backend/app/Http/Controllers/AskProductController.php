<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductAttachmentChunk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Pgvector\Laravel\Distance;
use Pgvector\Laravel\Vector;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;

class AskProductController extends Controller
{
    // Cast a wide net with cheap vector search, then let a rerank step (which reads
    // actual chunk content, not just embeddings) pick the ones that really answer the
    // question — a chunk mixing several topics can rank just outside the vector top-k
    // even when it contains the answer (see docs/design.md RAG notes).
    private const CANDIDATE_CHUNKS = 20;

    private const ANSWER_CHUNKS = 5;

    public function __invoke(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
        ]);

        $embeddings = Prism::embeddings()
            ->using(Provider::OpenAI, 'text-embedding-3-small')
            ->fromInput($data['question'])
            ->asEmbeddings();

        $candidates = ProductAttachmentChunk::query()
            ->whereHas('attachment', fn ($query) => $query->where('product_id', $product->id))
            ->nearestNeighbors('embedding', new Vector($embeddings->embeddings[0]->embedding), Distance::Cosine)
            ->limit(self::CANDIDATE_CHUNKS)
            ->get();

        if ($candidates->isEmpty()) {
            return response()->json([
                'answer' => 'No manual is available for this product yet.',
                'sources' => [],
            ]);
        }

        $chunks = $this->rerank($data['question'], $candidates);

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

    /**
     * @param  Collection<int, ProductAttachmentChunk>  $candidates
     * @return Collection<int, ProductAttachmentChunk>
     */
    private function rerank(string $question, Collection $candidates): Collection
    {
        $list = $candidates
            ->map(fn (ProductAttachmentChunk $chunk, int $i) => "[{$i}] {$chunk->content}")
            ->implode("\n\n");

        $schema = new ObjectSchema(
            name: 'relevant_excerpts',
            description: 'The excerpts that actually help answer the question.',
            properties: [
                new ArraySchema(
                    name: 'indices',
                    description: 'Indices of the relevant excerpts, most relevant first. Empty if none are relevant.',
                    items: new NumberSchema('index', 'The excerpt index, matching its [N] marker.'),
                ),
            ],
            requiredFields: ['indices'],
        );

        $response = Prism::structured()
            ->using(Provider::OpenAI, 'gpt-4o-mini')
            ->withSchema($schema)
            ->withPrompt("Question: {$question}\n\nExcerpts:\n{$list}")
            ->asStructured();

        /** @var array<int, mixed> $rawIndices */
        $rawIndices = $response->structured['indices'] ?? [];

        $indices = collect($rawIndices)
            ->map(fn ($index) => (int) $index)
            ->filter(fn (int $index) => $index >= 0 && $index < $candidates->count())
            ->unique()
            ->take(self::ANSWER_CHUNKS);

        // Can't tell "legitimately nothing relevant" apart from a malformed/empty
        // response, so fall back to the plain vector ranking rather than answering
        // with zero context either way — the final answer step still declines to
        // guess if none of these are actually relevant.
        if ($indices->isEmpty()) {
            return $candidates->take(self::ANSWER_CHUNKS)->values();
        }

        $candidateChunks = $candidates->values()->all();

        return $indices->map(fn (int $index) => $candidateChunks[$index])->values();
    }
}
