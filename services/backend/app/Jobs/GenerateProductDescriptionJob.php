<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// Placeholder generation until real description generation (RAG over S3 manual PDFs,
// see docs/design.md) is built — writes deterministic filler text so the queue plumbing
// can be exercised end-to-end before that step exists.
class GenerateProductDescriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $productId,
        public readonly string $locale,
    ) {}

    public function handle(): void
    {
        $product = Product::find($this->productId);

        if (! $product) {
            Log::info("GenerateProductDescriptionJob: product [{$this->productId}] no longer exists, skipping");

            return;
        }

        $name = $product->getTranslation('name', $this->locale, false)
            ?: $product->getTranslation('name', config('app.fallback_locale'));

        $product->setTranslation('description', $this->locale, "{$name} — description coming soon.");
        $product->save();
    }
}
