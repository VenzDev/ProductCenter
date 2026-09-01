<?php

declare(strict_types=1);

use App\Mcp\Http\ProtectedResourceMetadataController;
use App\Mcp\Servers\ProductCenterServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

// Local (stdio) transport — `php artisan mcp:start product-center`.
Mcp::local('product-center', ProductCenterServer::class);

// Streamable HTTP transport — remote clients (Claude Desktop / claude.ai) connect
// to https://<host>/mcp. Each request is authenticated from a Microsoft Entra
// bearer token via the 'mcp' guard (App\Providers\AppServiceProvider).
Mcp::web('/mcp', ProductCenterServer::class)->middleware('auth:mcp');

// OAuth 2.0 Protected Resource Metadata (RFC 9728) — tells MCP clients that Entra
// is the authorization server for /mcp. The route names are the ones Laravel MCP's
// AddWwwAuthenticateHeader middleware looks up when returning a 401.
Route::get('/.well-known/oauth-protected-resource', ProtectedResourceMetadataController::class)
    ->name('mcp.oauth.protected-resource');
Route::get('/.well-known/oauth-protected-resource/{path}', ProtectedResourceMetadataController::class)
    ->where('path', '.*')
    ->name('mcp.oauth.protected-resource.nested');
