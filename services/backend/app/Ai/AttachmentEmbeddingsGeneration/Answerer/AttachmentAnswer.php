<?php

declare(strict_types=1);

namespace App\Ai\AttachmentEmbeddingsGeneration\Answerer;

readonly class AttachmentAnswer
{
    /**
     * @param  array<int, array{attachment_id: int, chunk_index: int}>  $sources
     */
    public function __construct(
        public string $answer,
        public array $sources,
    ) {}
}
