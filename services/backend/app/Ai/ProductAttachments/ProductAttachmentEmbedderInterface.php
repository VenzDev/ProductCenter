<?php

declare(strict_types=1);

namespace App\Ai\ProductAttachments;

interface ProductAttachmentEmbedderInterface
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
