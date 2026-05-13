<?php

namespace Gurztac\AuthClient\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Gurztac\AuthClient\Services\HubAuthClient;
use Gurztac\AuthClient\Services\JwtValidator;
use Gurztac\AuthClient\Exceptions\InvalidJwtException;

class SsoController extends Controller
{
    public function __construct(
        protected HubAuthClient $hub,
        protected JwtValidator $validator,
    ) {}

    /**
     * GET /auth/sso/login → redirige al Hub con state CSRF.
     */
    public function login(Request $request)
    {
        $state = bin2hex(random_bytes(16));
        $request->session()->put('gurztac_sso_state', $state);

        $callback = url(config('gurztac-auth.sso.callback_path'));
        $plataforma = config('gurztac-auth.aud');
        $url = $this->hub->ssoLoginUrl($callback, $state, $plataforma);

        return redirect($url);
    }

    /**
     * GET /auth/sso/callback → recibe code, intercambia por JWT, guarda en session.
     */
    public function callback(Request $request)
    {
        $state = $request->query('state');
        $expectedState = $request->session()->pull('gurztac_sso_state');

        if (!$state || $state !== $expectedState) {
            return response('SSO state inválido (posible CSRF)', 403);
        }

        $code = $request->query('code');
        if (!$code) {
            return response('SSO sin code', 400);
        }

        $tokens = $this->hub->exchangeCode($code);
        if (!$tokens || empty($tokens['access_token'])) {
            return response('No se pudo intercambiar SSO code', 502);
        }

        try {
            $claims = $this->validator->validate($tokens['access_token']);
        } catch (InvalidJwtException $e) {
            return response('Token recibido no validó: ' . $e->getMessage(), 401);
        }

        // Guardar tokens
        $request->session()->put('gurztac_jwt', $tokens['access_token']);
        if (!empty($tokens['refresh_token'])) {
            $request->session()->put('gurztac_refresh', $tokens['refresh_token']);
        }
        $request->session()->put('gurztac_claims', $claims);

        // Auto-provision local user (opcional)
        if (config('gurztac-auth.auto_provision_local_user')) {
            $this->autoProvisionLocalUser($claims);
        }

        $intended = $request->session()->pull('gurztac_intended', config('gurztac-auth.sso.after_login_redirect'));
        return redirect($intended);
    }

    /**
     * POST /auth/sso/logout → revoca en Hub + limpia session local.
     */
    public function logout(Request $request)
    {
        $jwt = $request->session()->get('gurztac_jwt');
        if ($jwt) {
            $this->hub->logout($jwt);
        }
        $request->session()->forget(['gurztac_jwt', 'gurztac_refresh', 'gurztac_claims', 'gurztac_sso_state']);
        return redirect(config('gurztac-auth.sso.after_logout_redirect'));
    }

    protected function autoProvisionLocalUser(array $claims): void
    {
        $modelClass = config('gurztac-auth.local_user_model');
        $subField = config('gurztac-auth.sub_claim_field', 'gurztac_user_id');
        $userId = $claims['user_ecosistema_id'] ?? $claims['sub'] ?? null;

        if (!$userId || !class_exists($modelClass)) {
            return;
        }

        $model = $modelClass::firstOrCreate(
            [$subField => $userId],
            [
                'email' => $claims['email'] ?? null,
                'name' => $claims['nombre'] ?? $claims['email'] ?? 'Usuario',
                'password' => bcrypt(bin2hex(random_bytes(16))),
                'email_verified_at' => now(),
            ]
        );
    }
}
