<?php

namespace Gurztac\AuthClient\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Gurztac\AuthClient\Services\JwtValidator;
use Gurztac\AuthClient\Exceptions\InvalidJwtException;

/**
 * Valida el JWT del header Authorization y agrega los claims a $request->gurztac.
 * Si no hay token o es inválido → 401.
 */
class ValidarJwtGurztac
{
    public function __construct(
        protected JwtValidator $validator,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $jwt = $this->extractJwt($request);

        if (empty($jwt)) {
            return response()->json([
                'error' => 'unauthorized',
                'message' => 'Falta JWT en Authorization header',
            ], 401);
        }

        try {
            $claims = $this->validator->validate($jwt);
        } catch (InvalidJwtException $e) {
            return response()->json([
                'error' => 'jwt_invalid',
                'message' => $e->getMessage(),
            ], 401);
        }

        $request->attributes->set('gurztac_claims', $claims);
        $request->attributes->set('gurztac_user_id', $claims['user_ecosistema_id'] ?? $claims['sub'] ?? null);
        $request->attributes->set('gurztac_email', $claims['email'] ?? null);

        return $next($request);
    }

    protected function extractJwt(Request $request): ?string
    {
        // 1. Authorization: Bearer XXX
        $header = $request->header('Authorization', '');
        if (preg_match('/Bearer\s+(.+)$/i', $header, $m)) {
            return trim($m[1]);
        }

        // 2. Cookie (si está configurado)
        if (config('gurztac-auth.token_storage') === 'cookie') {
            return $request->cookie(config('gurztac-auth.cookie_name'));
        }

        // 3. Session (default fallback)
        if (config('gurztac-auth.token_storage') === 'session' && $request->hasSession()) {
            return $request->session()->get('gurztac_jwt');
        }

        return null;
    }
}
