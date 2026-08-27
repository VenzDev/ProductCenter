<?php

declare(strict_types=1);

namespace App\Ai\AttachmentEmbeddingsGeneration\Splitter;

class MinimalChunksSplitter implements ChunksSplitterInterface
{
    /**
     * @return array<int, string>
     */
    public function split(string $text): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        if ($text === '') {
            return [];
        }

        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $chunks[] = trim(mb_substr($text, $start, self::CHUNK_SIZE));
            $start += self::CHUNK_SIZE - self::CHUNK_OVERLAP;
        }

        return $chunks;
    }
}
