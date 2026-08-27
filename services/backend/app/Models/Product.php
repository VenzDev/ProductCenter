<?php

declare(strict_types=1);

namespace App\Models;

use App\Product\Observers\ProductImageObserver;
use App\Product\Observers\ProductSearchObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * HasTranslations stores name/description as a {locale: string} JSON map, but its
 * getAttributeValue() override resolves them to a plain string in the current app
 * locale on read — the opposite of what the trait's own registered `array` cast implies.
 *
 * @property-read string $name
 * @property-read string|null $description
 */
#[Fillable(['category_id', 'name', 'description', 'price_cents', 'currency', 'attributes', 'main_image'])]
#[Translatable(['name', 'description'])]
#[ObservedBy([ProductImageObserver::class, ProductSearchObserver::class])]
class Product extends Model
{
    use HasTranslations;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attributes' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<ProductAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ProductAttachment::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('order');
    }
}
