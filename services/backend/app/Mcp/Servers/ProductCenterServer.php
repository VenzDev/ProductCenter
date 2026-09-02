<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateProductTool;
use App\Mcp\Tools\ListCategoriesTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;

#[Name('Product Center')]
#[Version('0.1.0')]
#[Instructions('Lets an admin manage the Product Center catalog: list the leaf categories, then create products under one.')]
class ProductCenterServer extends Server
{
    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        ListCategoriesTool::class,
        CreateProductTool::class,
    ];
}
