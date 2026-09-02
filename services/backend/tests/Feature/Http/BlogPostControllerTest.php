<?php

declare(strict_types=1);

use App\Models\BlogPost;

function createPublishedBlogPost(array $overrides = []): BlogPost
{
    return BlogPost::create(array_merge([
        'title' => 'Hello World',
        'slug' => 'hello-world',
        'content' => '<p>Hi</p>',
        'published_at' => now()->subDay(),
    ], $overrides));
}

test('published blog posts can be listed', function () {
    $post = createPublishedBlogPost();

    $response = $this->getJson('/api/v1/blog-posts');

    $response->assertOk();
    $response->assertJsonPath('data.0.id', $post->id);
    $response->assertJsonPath('data.0.title', 'Hello World');
    $response->assertJsonPath('data.0.slug', 'hello-world');
    $response->assertJsonPath('data.0.content', '<p>Hi</p>');
});

test('draft blog posts are not listed', function () {
    BlogPost::create(['title' => 'Draft', 'slug' => 'draft', 'content' => '<p>Draft</p>']);

    $response = $this->getJson('/api/v1/blog-posts');

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
});

test('blog posts scheduled in the future are not listed', function () {
    createPublishedBlogPost(['slug' => 'future', 'published_at' => now()->addDay()]);

    $response = $this->getJson('/api/v1/blog-posts');

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
});

test('blog posts are listed most recently published first', function () {
    $older = createPublishedBlogPost(['slug' => 'older', 'published_at' => now()->subDays(2)]);
    $newer = createPublishedBlogPost(['slug' => 'newer', 'published_at' => now()->subDay()]);

    $response = $this->getJson('/api/v1/blog-posts');

    $response->assertOk();
    $response->assertJsonPath('data.0.id', $newer->id);
    $response->assertJsonPath('data.1.id', $older->id);
});

test('a single published blog post can be retrieved by its slug', function () {
    $post = createPublishedBlogPost();

    $response = $this->getJson('/api/v1/blog-posts/hello-world');

    $response->assertOk();
    $response->assertJson([
        'data' => [
            'id' => $post->id,
            'title' => 'Hello World',
            'slug' => 'hello-world',
            'content' => '<p>Hi</p>',
        ],
    ]);
});

test('the Accept-Language header switches the translated title and content', function () {
    $post = createPublishedBlogPost();
    $post->setTranslation('title', 'pl', 'Witaj świecie');
    $post->setTranslation('content', 'pl', '<p>Cześć</p>');
    $post->save();

    $response = $this->getJson('/api/v1/blog-posts/hello-world', ['Accept-Language' => 'pl']);

    $response->assertOk();
    $response->assertJsonPath('data.title', 'Witaj świecie');
    $response->assertJsonPath('data.content', '<p>Cześć</p>');
});

test('an unsupported Accept-Language falls back to the default locale', function () {
    createPublishedBlogPost();

    $response = $this->getJson('/api/v1/blog-posts/hello-world', ['Accept-Language' => 'de']);

    $response->assertOk();
    $response->assertJsonPath('data.title', 'Hello World');
});

test('a draft blog post returns 404', function () {
    BlogPost::create(['title' => 'Draft', 'slug' => 'draft', 'content' => '<p>Draft</p>']);

    $response = $this->getJson('/api/v1/blog-posts/draft');

    $response->assertNotFound();
});

test('retrieving a non-existent blog post returns 404', function () {
    $response = $this->getJson('/api/v1/blog-posts/does-not-exist');

    $response->assertNotFound();
});

test('a blog post with a preview image exposes the webp and thumbnail URLs', function () {
    $post = createPublishedBlogPost();
    $post->preview_image = "blog-post-images/{$post->id}/preview-image.jpg";
    $post->saveQuietly();

    $response = $this->getJson('/api/v1/blog-posts/hello-world');

    $response->assertOk();
    $response->assertJsonPath('data.preview_image.webp_url', fn ($url) => str_ends_with($url, "blog-post-images/{$post->id}/preview-image.webp"));
    $response->assertJsonPath('data.preview_image.thumbnail_webp_url', fn ($url) => str_ends_with($url, "blog-post-images/{$post->id}/preview-image-thumbnail.webp"));
});

test('a blog post without a preview image has a null preview_image', function () {
    createPublishedBlogPost();

    $response = $this->getJson('/api/v1/blog-posts/hello-world');

    $response->assertOk();
    $response->assertJsonPath('data.preview_image', null);
});
