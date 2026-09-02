<?php

declare(strict_types=1);

namespace App\Mcp\Http;

use App\Auth\Admin\MicrosoftAdminResolver;
use App\Models\Admin;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use stdClass;
use Throwable;

/**
 * Validates a Microsoft Entra access token presented as `Authorization: Bearer`
 * and resolves it to an Admin. Wired in as the `entra-mcp` request guard driver
 * (App\Providers\AppServiceProvider) behind the `auth:mcp` middleware on the web
 * MCP server. We are only the OAuth 2.1 resource server — Entra issues the token.
 */
class EntraTokenAuthenticator
{
    private const JWKS_CACHE_KEY = 'mcp.entra.jwks';

    public function __construct(private readonly MicrosoftAdminResolver $resolver) {}

    public function authenticate(Request $request): ?Admin
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            return null;
        }

        try {
            JWT::$leeway = 60;
            $claims = JWT::decode($token, $this->signingKeys());
        } catch (Throwable $e) {
            Log::debug('MCP Entra token rejected', ['reason' => $e->getMessage()]);

            return null;
        }

        $tenant = (string) config('mcp_auth.tenant_id');

        if ($this->claim($claims, 'iss') !== "https://login.microsoftonline.com/{$tenant}/v2.0") {
            return null;
        }

        /** @var list<string> $allowedAudiences */
        $allowedAudiences = config('mcp_auth.audiences');

        if (array_intersect($allowedAudiences, (array) ($claims->aud ?? [])) === []) {
            return null;
        }

        $scp = $this->claim($claims, 'scp');

        if (! in_array((string) config('mcp_auth.required_scope'), explode(' ', is_string($scp) ? $scp : ''), true)) {
            abort(403, 'The access token is missing the required MCP scope.');
        }

        $objectId = $this->claim($claims, 'oid');

        if (! is_string($objectId) || $objectId === '') {
            return null;
        }

        $username = $this->claim($claims, 'preferred_username') ?? $this->claim($claims, 'email');
        $name = $this->claim($claims, 'name');

        return $this->resolver->resolveFromClaims(
            $objectId,
            is_string($username) ? $username : null,
            is_string($name) ? $name : null,
        );
    }

    private function claim(stdClass $claims, string $name): mixed
    {
        return $claims->{$name} ?? null;
    }

    /**
     * Entra's signing keys, keyed by `kid`. Cached for an hour — Entra rotates
     * keys but publishes the new ones well ahead of using them.
     *
     * @return array<string, Key>
     */
    private function signingKeys(): array
    {
        $tenant = (string) config('mcp_auth.tenant_id');

        /** @var array<string, mixed> $jwks */
        $jwks = Cache::remember(
            self::JWKS_CACHE_KEY,
            now()->addHour(),
            fn (): array => (array) Http::get("https://login.microsoftonline.com/{$tenant}/discovery/v2.0/keys")
                ->throw()
                ->json()
        );

        // Entra's JWKS entries carry no "alg" field, so firebase/php-jwt needs a
        // default algorithm — Entra signs its v2 access tokens with RS256.
        return JWK::parseKeySet($jwks, 'RS256');
    }
}
