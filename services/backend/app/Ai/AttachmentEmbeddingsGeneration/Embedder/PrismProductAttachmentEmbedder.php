<?php

declare(strict_types=1);

namespace App\Ai\AttachmentEmbeddingsGeneration\Embedder;

use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\ValueObjects\Embedding;

class PrismProductAttachmentEmbedder implements ProductAttachmentEmbedderInterface
{
    private const string EMBEDDING_MODEL = 'text-embedding-3-small';

    private const string TEXT_MODEL = 'gpt-4o-mini';

    public function embed(array $texts): array
    {
        $response = Prism::embeddings()
            ->using(Provider::OpenAI, self::EMBEDDING_MODEL)
            ->fromArray($texts)
            ->asEmbeddings();

        return array_map(
            fn (Embedding $embedding): array => array_map(fn ($value): float => (float) $value, $embedding->embedding),
            $response->embeddings,
        );
    }

    public function selectRelevantExcerpts(string $question, array $excerpts, int $limit): array
    {
        $list = collect($excerpts)
            ->map(fn (string $excerpt, int $i) => "[{$i}] {$excerpt}")
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
            ->using(Provider::OpenAI, self::TEXT_MODEL)
            ->withSchema($schema)
            ->withPrompt("Question: {$question}\n\nExcerpts:\n{$list}")
            ->asStructured();

        /** @var array<int, mixed> $rawIndices */
        $rawIndices = $response->structured['indices'] ?? [];

        return collect($rawIndices)
            ->map(fn ($index) => (int) $index)
            ->filter(fn (int $index) => $index >= 0 && $index < count($excerpts))
            ->unique()
            ->take($limit)
            ->values()
            ->all();
    }

    public function answerQuestion(string $question, string $context): string
    {
        $response = Prism::text()
            ->using(Provider::OpenAI, self::TEXT_MODEL)
            ->withSystemPrompt(
                "Answer the user's question using only the numbered manual excerpts below. ".
                "If the excerpts don't contain the answer, say so instead of guessing. ".
                'Reply in plain flowing prose: no line breaks and no bullet/numbered lists — '.
                "if you need to list multiple items, separate them with commas instead.\n\n{$context}"
            )
            ->withPrompt($question)
            ->asText();

        return $response->text;
    }
}
