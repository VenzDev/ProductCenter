<?php

namespace App\Services\Ai;

/**
 * Port for the AI operations the manual RAG pipeline needs (ingestion embedding,
 * question embedding, reranking, answer generation) — kept provider-agnostic so
 * PrismManualAiService (or any other adapter) can change without touching callers.
 */
interface ManualAiService
{
    /**
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>> one embedding vector per input, same order
     */
    public function embed(array $texts): array;

    /**
     * @param  array<int, string>  $excerpts
     * @return array<int, int> indices into $excerpts, most relevant first, capped at $limit
     */
    public function selectRelevantExcerpts(string $question, array $excerpts, int $limit): array;

    public function answerQuestion(string $question, string $context): string;
}
