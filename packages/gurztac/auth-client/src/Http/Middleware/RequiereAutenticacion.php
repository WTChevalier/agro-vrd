<?php

namespace Gurztac\AuthClient\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Gurztac\AuthClient\Services\JwtValidator;
use Gurztac\AuthClient\Exceptions\InvalidJwtException;

/**
 * Versión web del middleware: si no hay JWT, redirige a SSO login en lugar de 401.
 */
class RequiereAutenticacion
{
    public function __construct(
        protected JwtValidator $validator,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $jwt = $request->hasSession() ? $request->session()->get('gurztac_jwt') : null;

        if (empty($jwt) && $request->hasCookie(config('gurztac-auth.cookie_name'))) {
            $jwt = $request->cookie(config('gurztac-auth.cookie_name'));
        }

        if (empty($jwt)) {
            return $this->redirectToSso($request);
        }

        try {
            $claims = $this->validator->validate($jwt);
        } catch (InvalidJwtException $e) {
            // Token expirado o inválido → forzar relogin
            if ($request->hasSession()) {
                $request->session()->forget('gurztac_jwt');
            }
            return $this->redirectToSso($request);
        }

        $request->attributes->set('gurztac_claims', $claims);
        $request->attributes->set('gurztac_user_id', $claims['user_ecosistema_id'] ?? $claims['sub'] ?? null);
        $request->attributes->set('gurztac_email', $claims['email'] ?? null);

        return $next($request);
    }

    protected function redirectToSso(Request $request): Response
    {
        if ($request->hasSession()) {
            $request->session()->put('gurztac_intended', $request->fullUrl());
        }
        return redirect(config('gurztac-auth.sso.login_path'));
    }
}
