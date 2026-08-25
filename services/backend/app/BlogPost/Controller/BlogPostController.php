<?php

declare(strict_types=1);

namespace App\BlogPost\Controller;

use App\BlogPost\Resource\BlogPostResource;
use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlogPostController extends Controller
{
    /**
     * List published blog posts, most recently published first.
     */
    public function index(): AnonymousResourceCollection
    {
        return BlogPostResource::collection(
            BlogPost::query()->published()->orderByDesc('published_at')->paginate()
        );
    }

    /**
     * Retrieve a single published blog post by its slug.
     */
    public function show(BlogPost $blogPost): BlogPostResource
    {
        abort_unless((bool) $blogPost->published_at?->isPast(), 404);

        return new BlogPostResource($blogPost);
    }
}
