<?php

declare(strict_types=1);

use App\BlogPost\Support\BlogPostImagePaths;
use App\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Filament\Resources\BlogPosts\Pages\EditBlogPost;
use App\Models\Admin;
use App\Models\BlogPost;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('an admin can list blog posts', function () {
    $admin = Admin::factory()->create();
    BlogPost::create(['title' => 'Hello World', 'slug' => 'hello-world', 'content' => '<p>Hi</p>']);

    $response = $this->actingAs($admin, 'admin')->get('/admin/blog-posts');

    $response->assertOk()->assertSee('Hello World');
});

test('an admin can create a blog post with a wysiwyg body', function () {
    $admin = Admin::factory()->create();
    $this->actingAs($admin, 'admin');

    Livewire::test(CreateBlogPost::class)
        ->fillForm([
            'title' => 'Hello World',
            'slug' => 'hello-world',
            'content' => '<p>Hi</p>',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $post = BlogPost::where('slug', 'hello-world')->first();
    expect($post)->not->toBeNull();
    expect($post->title)->toBe('Hello World');
    expect($post->content)->toBe('<p>Hi</p>');
    expect($post->published_at)->toBeNull();
});

test('a blog post slug must be unique', function () {
    $admin = Admin::factory()->create();
    BlogPost::create(['title' => 'Hello World', 'slug' => 'hello-world', 'content' => '<p>Hi</p>']);
    $this->actingAs($admin, 'admin');

    Livewire::test(CreateBlogPost::class)
        ->fillForm([
            'title' => 'Hello World Again',
            'slug' => 'hello-world',
            'content' => '<p>Hi again</p>',
        ])
        ->call('create')
        ->assertHasFormErrors(['slug']);
});

test('an admin can update a blog post', function () {
    $admin = Admin::factory()->create();
    $post = BlogPost::create(['title' => 'Hello World', 'slug' => 'hello-world', 'content' => '<p>Hi</p>']);
    $this->actingAs($admin, 'admin');

    Livewire::test(EditBlogPost::class, ['record' => $post->getRouteKey()])
        ->fillForm(['content' => '<p>Updated</p>'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($post->refresh()->content)->toBe('<p>Updated</p>');
});

test('an admin can upload a blog post preview image to the s3 disk', function () {
    Storage::fake('s3');

    $admin = Admin::factory()->create();
    $this->actingAs($admin, 'admin');
    $image = UploadedFile::fake()->image('preview.jpg');

    Livewire::test(CreateBlogPost::class)
        ->fillForm([
            'title' => 'Hello World',
            'slug' => 'hello-world',
            'content' => '<p>Hi</p>',
            'preview_image' => $image,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $post = BlogPost::where('slug', 'hello-world')->first();
    expect($post->preview_image)->toBe("blog-post-images/{$post->id}/preview-image.jpg");
    Storage::disk('s3')->assertExists($post->preview_image);

    // RelocateUploadedImageJob and the GenerateWebpImageJob it dispatches both run
    // synchronously (sync queue driver in tests), so their output is already in place
    // once the form submission above returns.
    Storage::disk('s3')->assertExists(BlogPostImagePaths::webp($post->id));
    Storage::disk('s3')->assertExists(BlogPostImagePaths::thumbnailWebp($post->id));
});
