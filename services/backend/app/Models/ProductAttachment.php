<?php

namespace App\Models;

use App\Ai\Jobs\GenerateAttachmentEmbeddingsJob;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['product_id', 'path', 'label'])]
class ProductAttachment extends Model
{
    const UPDATED_AT = null;

    protected static function booted(): void
    {
        // Only PDFs go through the embedding pipeline (see GenerateAttachmentEmbeddingsJob).
        static::created(function (ProductAttachment $attachment): void {
            if (Str::endsWith(Str::lower($attachment->path), '.pdf')) {
                GenerateAttachmentEmbeddingsJob::dispatch($attachment->id);
            }
        });
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<ProductAttachmentChunk, $this>
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(ProductAttachmentChunk::class);
    }
}
