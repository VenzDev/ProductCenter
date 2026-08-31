<?php

declare(strict_types=1);

use App\Mcp\Servers\ProductCenterServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('product-center', ProductCenterServer::class);
