<?php

declare(strict_types=1);

use App\BlogPost\Support\BlogPostImagePaths;
use App\Images\Jobs\RelocateUploadedImageJob;
use App\Models\BlogPost;
use Illuminate\Support\Facades\Bus;

test('creating a blog post with a preview image dispatches the relocation job', function () {
    Bus::fake([RelocateUploadedImageJob::class]);

    $post = BlogPost::create([
        'title' => 'Hello World',
        'slug' => 'hello-world',
        'content' => '<p>Hi</p>',
        'preview_image' => 'blog-post-images/tmp/abc.jpg',
    ]);

    Bus::assertDispatched(RelocateUploadedImageJob::class, fn ($job) => $job->modelClass === BlogPost::class
        && $job->modelId === $post->id
        && $job->imageColumn === 'preview_image'
        && $job->imagePathsClass === BlogPostImagePaths::class);
});

test('replacing the preview image dispatches the relocation job', function () {
    Bus::fake([RelocateUploadedImageJob::class]);
    $post = BlogPost::create([
        'title' => 'Hello World',
        'slug' => 'hello-world',
        'content' => '<p>Hi</p>',
        'preview_image' => 'blog-post-images/tmp/abc.jpg',
    ]);

    $post->update(['preview_image' => 'blog-post-images/tmp/def.jpg']);

    Bus::assertDispatchedTimes(RelocateUploadedImageJob::class, 2);
});

test('saving a blog post without changing the preview image does not dispatch the relocation job', function () {
    Bus::fake([RelocateUploadedImageJob::class]);
    $post = BlogPost::create([
        'title' => 'Hello World',
        'slug' => 'hello-world',
        'content' => '<p>Hi</p>',
        'preview_image' => 'blog-post-images/tmp/abc.jpg',
    ]);

    $post->update(['title' => 'Hello World Again']);

    Bus::assertDispatchedTimes(RelocateUploadedImageJob::class, 1);
});
