<?php

declare(strict_types=1);

use App\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Filament\Resources\BlogPosts\Pages\EditBlogPost;
use App\Models\Admin;
use App\Models\BlogPost;
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
