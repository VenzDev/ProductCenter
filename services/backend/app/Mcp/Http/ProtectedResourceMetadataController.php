<?php

declare(strict_types=1);

namespace App\Mcp\Http;

use Illuminate\Http\JsonResponse;

/**
 * OAuth 2.0 Protected Resource Metadata (RFC 9728) for the web MCP server.
 * Points MCP clients (Claude) at Microsoft Entra as the external authorization
 * server. Served at /.well-known/oauth-protected-resource[/{path}]; the route
 * names match what Laravel MCP's AddWwwAuthenticateHeader middleware looks up so
 * a 401 from `auth:mcp` carries the correct `resource_metadata` pointer.
 */
class ProtectedResourceMetadataController
{
    public function __invoke(?string $path = null): JsonResponse
    {
        $tenant = (string) config('mcp_auth.tenant_id');

        return response()->json([
            'resource' => config('mcp_auth.resource') ?: url('/mcp'),
            'authorization_servers' => ["https://login.microsoftonline.com/{$tenant}/v2.0"],
            'scopes_supported' => array_filter([(string) config('mcp_auth.scope_uri')]),
            'bearer_methods_supported' => ['header'],
        ]);
    }
}
