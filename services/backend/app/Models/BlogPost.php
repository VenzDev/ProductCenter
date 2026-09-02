<?php

declare(strict_types=1);

namespace App\Models;

use App\BlogPost\Observers\BlogPostImageObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * HasTranslations stores title/content as a {locale: string} JSON map but resolves
 * them to a plain string in the current app locale on read (see App\Models\Product).
 *
 * @property-read string $title
 * @property-read string $content
 * @property-read Carbon|null $published_at
 */
#[Fillable(['title', 'slug', 'content', 'published_at', 'preview_image'])]
#[Translatable(['title', 'content'])]
#[ObservedBy(BlogPostImageObserver::class)]
class BlogPost extends Model
{
    use HasTranslations;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<BlogPost>  $query
     * @return Builder<BlogPost>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }
}
