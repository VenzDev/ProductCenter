<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

// Only ever invoked explicitly (`--class=BlogPostSeeder`) — not part of
// DatabaseSeeder::run() — so it stays out of the default dev/prod seeding
// path. Used to give the e2e stack a known, published post to browse.
class BlogPostSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        BlogPost::factory()->create([
            'title' => 'Hello World',
            'slug' => 'hello-world',
        ]);
    }
}
