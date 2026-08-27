<?php

declare(strict_types=1);

namespace App\BlogPost\Resource;

use App\BlogPost\Support\BlogPostImagePaths;
use App\Images\Support\ImageUrlResolver;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BlogPost
 */
class BlogPostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'published_at' => $this->published_at?->toIso8601String(),
            'preview_image' => $this->preview_image
                ? ImageUrlResolver::resolve(BlogPostImagePaths::class, $this->id)
                : null,
        ];
    }
}
