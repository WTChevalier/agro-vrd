<?php

namespace Gurztac\AuthClient\Traits;

use Illuminate\Http\Request;

/**
 * Helper trait para Controllers que necesitan acceder a los claims del JWT.
 */
trait HasGurztacAuth
{
    protected function gurztacUserId(Request $request): ?string
    {
        return $request->attributes->get('gurztac_user_id');
    }

    protected function gurztacEmail(Request $request): ?string
    {
        return $request->attributes->get('gurztac_email');
    }

    protected function gurztacClaims(Request $request): array
    {
        return $request->attributes->get('gurztac_claims', []);
    }

    protected function gurztacClaim(Request $request, string $name, mixed $default = null): mixed
    {
        $claims = $this->gurztacClaims($request);
        return $claims[$name] ?? $default;
    }
}
