<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | MCP HTTP server — Microsoft Entra token validation
    |--------------------------------------------------------------------------
    |
    | The web MCP server (routes/ai.php → Mcp::web('/mcp', ...)) is an OAuth 2.1
    | *resource server*: it validates access tokens issued by Microsoft Entra
    | (the external authorization server) and never runs its own OAuth flow.
    | See docs/adl.md and app/Mcp/Http/EntraTokenAuthenticator.php.
    |
    | Same tenant as the admin-panel SSO, but a dedicated pair of Entra app
    | registrations (an "API" app exposing the scope + a client app for Claude).
    |
    */

    // Defaults to the admin-panel SSO tenant — it's the same directory.
    'tenant_id' => env('MCP_ENTRA_TENANT_ID', env('AZURE_OPENID_TENANT_ID')),

    // Accepted "aud" claim values, comma-separated. Entra puts either the API
    // app's Application ID URI (api://<guid>) or its bare client-id GUID here
    // depending on config — listing both removes the guesswork.
    'audiences' => array_values(array_filter(
        array_map('trim', explode(',', (string) env('MCP_ENTRA_AUDIENCE'))),
        fn (string $value): bool => $value !== '',
    )),

    // The `resource` value advertised in the protected-resource metadata (RFC 9728)
    // and echoed back by clients as the RFC 8707 resource parameter. Two hard
    // constraints have to agree:
    //   - strict MCP clients (the @modelcontextprotocol SDK / mcp-remote) require
    //     it to be a prefix of the URL they connected to;
    //   - Entra requires it to be a registered Application ID URI of the API app,
    //     matching the requested scope (else AADSTS9010010).
    // The way to satisfy both: verify the domain in Entra and set the API app's
    // Application ID URI to the real MCP URL (https://<host>/mcp). Then leave this
    // blank — it falls back to url('/mcp'). Set it only to force a different value.
    'resource' => env('MCP_ENTRA_RESOURCE'),

    // Fully-qualified scope advertised to MCP clients in the protected-resource
    // metadata, e.g. api://<api-app-guid>/mcp.use.
    'scope_uri' => env('MCP_ENTRA_SCOPE_URI'),

    // The short scope name that must appear in the token's "scp" claim.
    'required_scope' => env('MCP_ENTRA_REQUIRED_SCOPE', 'mcp.use'),
];
