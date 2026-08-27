<?php

declare(strict_types=1);

namespace App\Ai\AttachmentEmbeddingsGeneration\Splitter;

interface ChunksSplitterInterface
{
    const int CHUNK_SIZE = 1000;

    const int CHUNK_OVERLAP = 150;

    /**
     * @return array<int, string>
     */
    public function split(string $text): array;
}
