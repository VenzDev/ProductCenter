<?php

declare(strict_types=1);

namespace App\Ai\AttachmentEmbeddingsGeneration\Job;

use App\Ai\AttachmentEmbeddingsGeneration\Embedder\ProductAttachmentEmbedderInterface;
use App\Ai\AttachmentEmbeddingsGeneration\Splitter\ChunksSplitterInterface;
use App\Models\ProductAttachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Pgvector\Laravel\Vector;
use Smalot\PdfParser\Parser;

class GenerateAttachmentEmbeddingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $attachmentId,
    ) {}

    public function handle(
        ProductAttachmentEmbedderInterface $ai,
        ChunksSplitterInterface $splitter
    ): void {
        $attachment = ProductAttachment::find($this->attachmentId);

        if (! $attachment) {
            Log::info("GenerateAttachmentEmbeddingsJob: attachment [{$this->attachmentId}] no longer exists, skipping");

            return;
        }

        if ($attachment->chunks()->exists()) {
            Log::info("GenerateAttachmentEmbeddingsJob: attachment [{$this->attachmentId}] already has embeddings, skipping");

            return;
        }

        $pdf = Storage::disk('s3')->get($attachment->path);

        if ($pdf === null) {
            Log::info("GenerateAttachmentEmbeddingsJob: attachment [{$this->attachmentId}] file missing from S3, skipping");

            return;
        }

        $text = (new Parser)->parseContent($pdf)->getText();
        $chunks = $splitter->split($text);

        if ($chunks === []) {
            Log::info("GenerateAttachmentEmbeddingsJob: attachment [{$this->attachmentId}] has no extractable text, skipping");

            return;
        }

        $embeddings = $ai->embed($chunks);

        DB::transaction(function () use ($attachment, $chunks, $embeddings): void {
            foreach ($chunks as $index => $content) {
                $attachment->chunks()->create([
                    'chunk_index' => $index,
                    'content' => $content,
                    'embedding' => new Vector($embeddings[$index]),
                ]);
            }
        });
    }
}
