<?php

declare(strict_types=1);

use App\Models\Admin;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;

/**
 * Exercises the web MCP server's Entra bearer-token guard without a real Entra:
 * we generate an RSA keypair, publish it as the cached JWKS, and sign our own
 * tokens. Tenant/audience/scope come from .env.testing (config/mcp_auth.php).
 */
beforeEach(function () {
    $key = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    openssl_pkey_export($key, $this->privateKey);
    $details = openssl_pkey_get_details($key);

    $b64url = fn (string $bin): string => rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');

    Cache::forget('mcp.entra.jwks');
    Cache::put('mcp.entra.jwks', ['keys' => [[
        'kty' => 'RSA',
        'use' => 'sig',
        'alg' => 'RS256',
        'kid' => 'test-key',
        'n' => $b64url($details['rsa']['n']),
        'e' => $b64url($details['rsa']['e']),
    ]]]);

    $this->token = function (array $overrides = []): string {
        $claims = array_merge([
            'iss' => 'https://login.microsoftonline.com/test-tenant/v2.0',
            'aud' => 'api://product-center-mcp-test',
            'scp' => 'mcp.use',
            'oid' => 'oid-mcp-admin',
            'preferred_username' => 'mcp.admin@example.com',
            'name' => 'MCP Admin',
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 3600,
        ], $overrides);

        return JWT::encode(array_filter($claims, fn ($v) => $v !== null), $this->privateKey, 'RS256', 'test-key');
    };

    config(['services.microsoft.allowed_domain' => 'example.com']);
});

function ping(array $headers = []): TestResponse
{
    return test()->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], $headers);
}

test('the protected-resource metadata points MCP clients at Entra', function () {
    $this->getJson('/.well-known/oauth-protected-resource')
        ->assertOk()
        ->assertJsonPath('resource', url('/mcp'))
        ->assertJsonPath('authorization_servers.0', 'https://login.microsoftonline.com/test-tenant/v2.0')
        ->assertJsonPath('scopes_supported.0', 'api://product-center-mcp-test/mcp.use');

    // The nested variant (what WWW-Authenticate points at) serves the same document.
    $this->getJson('/.well-known/oauth-protected-resource/mcp')
        ->assertOk()->assertJsonPath('resource', url('/mcp'));
});

test('an explicit MCP_ENTRA_RESOURCE overrides the url() fallback', function () {
    config(['mcp_auth.resource' => 'api://product-center-mcp-test']);

    $this->getJson('/.well-known/oauth-protected-resource')
        ->assertOk()->assertJsonPath('resource', 'api://product-center-mcp-test');
});

test('a request without a token is rejected with a resource_metadata pointer', function () {
    $response = ping();

    $response->assertUnauthorized();
    expect($response->headers->get('WWW-Authenticate'))
        ->toContain('resource_metadata=')
        ->toContain('/.well-known/oauth-protected-resource/mcp');
});

test('a token from the wrong issuer is rejected', function () {
    ping(['Authorization' => 'Bearer '.($this->token)(['iss' => 'https://login.microsoftonline.com/other/v2.0'])])
        ->assertUnauthorized();
});

test('a token for the wrong audience is rejected', function () {
    ping(['Authorization' => 'Bearer '.($this->token)(['aud' => 'api://someone-else'])])
        ->assertUnauthorized();
});

test('any of the comma-separated accepted audiences is honoured', function () {
    config(['mcp_auth.audiences' => ['api://product-center-mcp-test', 'some-guid']]);

    ping(['Authorization' => 'Bearer '.($this->token)(['aud' => 'some-guid'])])->assertOk();
});

test('an expired token is rejected', function () {
    // Well past the authenticator's 60s leeway.
    ping(['Authorization' => 'Bearer '.($this->token)(['exp' => time() - 300, 'nbf' => time() - 600, 'iat' => time() - 600])])
        ->assertUnauthorized();
});

test('a token missing the mcp.use scope is forbidden', function () {
    ping(['Authorization' => 'Bearer '.($this->token)(['scp' => 'openid profile'])])
        ->assertForbidden();
});

test('a valid token provisions the admin just-in-time and authenticates the request', function () {
    $response = ping(['Authorization' => 'Bearer '.($this->token)()]);

    $response->assertOk()->assertJsonPath('result', []);

    $admin = Admin::where('microsoft_id', 'oid-mcp-admin')->first();
    expect($admin)->not->toBeNull();
    expect($admin->email)->toBe('mcp.admin@example.com');

    // A second call reuses the same row.
    ping(['Authorization' => 'Bearer '.($this->token)()])->assertOk();
    expect(Admin::where('microsoft_id', 'oid-mcp-admin')->count())->toBe(1);
});

test('a valid token for a user outside the allowed domain is rejected', function () {
    ping(['Authorization' => 'Bearer '.($this->token)(['oid' => 'oid-outsider', 'preferred_username' => 'x@other.com'])])
        ->assertUnauthorized();

    expect(Admin::where('microsoft_id', 'oid-outsider')->exists())->toBeFalse();
});
