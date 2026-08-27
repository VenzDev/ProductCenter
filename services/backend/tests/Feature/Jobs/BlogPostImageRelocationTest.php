<?php

declare(strict_types=1);

use App\BlogPost\Support\BlogPostImagePaths;
use App\Images\Jobs\RelocateUploadedImageJob;
use App\Models\BlogPost;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

function fakeBlogPostJpegBytes(): string
{
    return (string) (new ImageManager(new Driver))->create(20, 20)->toJpeg();
}

function createBlogPostWithoutImage(): BlogPost
{
    return BlogPost::create([
        'title' => 'Hello World',
        'slug' => 'hello-world',
        'content' => '<p>Hi</p>',
    ]);
}

function stageBlogPostImage(BlogPost $blogPost, string $stagingKey): void
{
    Storage::disk('s3')->put($stagingKey, fakeBlogPostJpegBytes());
    // saveQuietly avoids the real BlogPostImageObserver dispatch — these tests drive the job directly.
    $blogPost->preview_image = $stagingKey;
    $blogPost->saveQuietly();
}

function relocateBlogPostImage(BlogPost $blogPost): void
{
    // GenerateWebpImageJob::dispatch() inside the job runs synchronously here (sync
    // queue driver in tests), so both variants exist by the time this call returns.
    (new RelocateUploadedImageJob(BlogPost::class, $blogPost->id, 'preview_image', BlogPostImagePaths::class))->handle();
}

test('handling the job relocates a staged upload and generates both webp variants', function () {
    Storage::fake('s3');
    $post = createBlogPostWithoutImage();
    stageBlogPostImage($post, 'blog-post-images/tmp/abc123.jpg');

    relocateBlogPostImage($post);

    $fresh = $post->fresh();
    expect($fresh->preview_image)->toBe("blog-post-images/{$post->id}/preview-image.jpg");

    Storage::disk('s3')->assertMissing('blog-post-images/tmp/abc123.jpg');
    Storage::disk('s3')->assertExists($fresh->preview_image);
    Storage::disk('s3')->assertExists(BlogPostImagePaths::webp($post->id));
    Storage::disk('s3')->assertExists(BlogPostImagePaths::thumbnailWebp($post->id));
});

test('replacing the image removes the stale canonical original when the extension changes', function () {
    Storage::fake('s3');
    $post = createBlogPostWithoutImage();
    stageBlogPostImage($post, "blog-post-images/{$post->id}/uploads/first.jpg");
    relocateBlogPostImage($post);

    stageBlogPostImage($post->fresh(), "blog-post-images/{$post->id}/uploads/second.png");

    relocateBlogPostImage($post->fresh());

    $fresh = $post->fresh();
    expect($fresh->preview_image)->toBe("blog-post-images/{$post->id}/preview-image.png");
    Storage::disk('s3')->assertMissing("blog-post-images/{$post->id}/preview-image.jpg");
    Storage::disk('s3')->assertExists("blog-post-images/{$post->id}/preview-image.png");
});

test('a job for a blog post that no longer exists does nothing without throwing', function () {
    expect(fn () => (new RelocateUploadedImageJob(BlogPost::class, 999999, 'preview_image', BlogPostImagePaths::class))->handle())
        ->not->toThrow(Throwable::class);
});
