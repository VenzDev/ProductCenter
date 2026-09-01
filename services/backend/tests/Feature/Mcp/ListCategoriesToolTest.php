<?php

declare(strict_types=1);

use App\Mcp\Servers\ProductCenterServer;
use App\Mcp\Tools\ListCategoriesTool;
use App\Models\Category;

test('it lists only leaf categories, with their full path', function () {
    $electronics = Category::create(['name' => 'Electronics']);
    $headphones = Category::create(['name' => 'Headphones', 'parent_id' => $electronics->id]);
    $books = Category::create(['name' => 'Books']);

    $response = ProductCenterServer::tool(ListCategoriesTool::class, []);

    $response->assertOk()
        ->assertSee("#{$headphones->id}\tElectronics / Headphones")
        ->assertSee("#{$books->id}\tBooks");

    // "Electronics" has a child, so it is not itself a leaf.
    $response->assertDontSee("#{$electronics->id}\tElectronics");
});

test('it reports when there are no categories', function () {
    $response = ProductCenterServer::tool(ListCategoriesTool::class, []);

    $response->assertOk()->assertSee('No categories exist yet');
});
