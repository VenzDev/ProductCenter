<?php

declare(strict_types=1);

namespace App\Models;

use App\BlogPost\Observers\BlogPostImageObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property-read Carbon|null $published_at
 */
#[Fillable(['title', 'slug', 'content', 'published_at', 'preview_image'])]
#[ObservedBy(BlogPostImageObserver::class)]
class BlogPost extends Model
{
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
