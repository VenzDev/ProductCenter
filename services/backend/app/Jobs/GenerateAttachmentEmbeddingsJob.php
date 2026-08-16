<?php

namespace App\Jobs;

use App\Models\ProductAttachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Pgvector\Laravel\Vector;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Smalot\PdfParser\Parser;

class GenerateAttachmentEmbeddingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CHUNK_SIZE = 1000;

    private const CHUNK_OVERLAP = 150;

    public function __construct(
        public readonly int $attachmentId,
    ) {}

    public function handle(): void
    {
        $attachment = ProductAttachment::find($this->attachmentId);

        if (! $attachment) {
            Log::info("GenerateAttachmentEmbeddingsJob: attachment [{$this->attachmentId}] no longer exists, skipping");

            return;
        }

        $pdf = Storage::disk('s3')->get($attachment->path);

        if ($pdf === null) {
            Log::info("GenerateAttachmentEmbeddingsJob: attachment [{$this->attachmentId}] file missing from S3, skipping");

            return;
        }

        $text = (new Parser)->parseContent($pdf)->getText();
        $chunks = $this->splitIntoChunks($text);

        if ($chunks === []) {
            Log::info("GenerateAttachmentEmbeddingsJob: attachment [{$this->attachmentId}] has no extractable text, skipping");

            return;
        }

        $response = Prism::embeddings()
            ->using(Provider::OpenAI, 'text-embedding-3-small')
            ->fromArray($chunks)
            ->asEmbeddings();

        foreach ($chunks as $index => $content) {
            $attachment->chunks()->create([
                'chunk_index' => $index,
                'content' => $content,
                'embedding' => new Vector($response->embeddings[$index]->embedding),
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function splitIntoChunks(string $text): array
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
