<?php

declare(strict_types=1);

namespace App\Auth\Admin;

use App\Models\Admin;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class MicrosoftAdminResolver implements ResolverInterface
{
    public function resolve(SocialiteUser $microsoftUser): ?Admin
    {
        return $this->resolveFromClaims(
            $microsoftUser->getId(),
            $microsoftUser->getEmail(),
            $microsoftUser->getName(),
        );
    }

    /**
     * Match (or just-in-time provision) an Admin from identity claims — shared by
     * the browser SSO flow above and the MCP bearer-token guard
     * (App\Mcp\Http\EntraTokenAuthenticator). `$microsoftId` is the Entra object
     * id (`oid` / Graph `id`).
     */
    public function resolveFromClaims(string $microsoftId, ?string $email, ?string $name): ?Admin
    {
        return Admin::where('microsoft_id', $microsoftId)->first()
            ?? $this->provision($microsoftId, $email, $name);
    }

    // Just-in-time provisioning: the OAuth app registration is already single-tenant
    // (AZURE_OPENID_TENANT_ID), so this only widens who *within that tenant* becomes an
    // admin — it doesn't open login to outsiders. Matched against `userPrincipalName`
    // rather than the `mail` claim because UPN is always populated and, for B2B guests,
    // takes the form `user_otherdomain.com#EXT#@tenant...` — which won't match a plain
    // domain suffix, so guests are naturally excluded too.
    private function provision(string $microsoftId, ?string $email, ?string $name): ?Admin
    {
        $allowedDomain = config('services.microsoft.allowed_domain');

        if (! $allowedDomain || ! $email || ! str_ends_with(strtolower($email), '@'.strtolower($allowedDomain))) {
            return null;
        }

        return Admin::create([
            'name' => $name ?? $email,
            'email' => $email,
            'microsoft_id' => $microsoftId,
        ]);
    }
}
