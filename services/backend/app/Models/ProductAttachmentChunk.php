<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

#[Fillable(['product_attachment_id', 'chunk_index', 'content', 'embedding'])]
class ProductAttachmentChunk extends Model
{
    use HasNeighbors;

    const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'embedding' => Vector::class,
        ];
    }

    /**
     * @return BelongsTo<ProductAttachment, $this>
     */
    public function attachment(): BelongsTo
    {
        return $this->belongsTo(ProductAttachment::class, 'product_attachment_id');
    }
}
