<?php

namespace Gurztac\AuthClient\Services;

use Illuminate\Support\Facades\Http;

class HubAuthClient
{
    public function __construct(
        protected string $hubUrl,
    ) {}

    /**
     * Inicia el flujo SSO redirigiendo al Hub.
     * @param  string  $plataforma  Slug de la plataforma (codigo_subdominio) — REQUERIDO por el Hub
     */
    public function ssoLoginUrl(string $callbackUrl, ?string $state = null, ?string $plataforma = null): string
    {
        $state ??= bin2hex(random_bytes(16));
        $params = [
            'callback' => $callbackUrl,
            'state' => $state,
        ];
        if (!empty($plataforma)) {
            $params['plataforma'] = $plataforma;
        }
        return rtrim($this->hubUrl, '/') . '/sso/iniciar?' . http_build_query($params);
    }

    /**
     * Intercambia el code recibido en el callback por un JWT.
     */
    public function exchangeCode(string $code): ?array
    {
        $resp = Http::timeout(10)->post($this->hubUrl . '/api/idp/sso/exchange', [
            'code' => $code,
        ]);
        return $resp->ok() ? $resp->json() : null;
    }

    /**
     * Refresca un access_token usando refresh_token.
     */
    public function refresh(string $refreshToken): ?array
    {
        $resp = Http::timeout(10)->post($this->hubUrl . '/api/idp/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);
        return $resp->ok() ? $resp->json() : null;
    }

    /**
     * Logout en el Hub.
     */
    public function logout(string $accessToken): bool
    {
        $resp = Http::timeout(10)
            ->withToken($accessToken)
            ->post($this->hubUrl . '/api/idp/auth/logout');
        return $resp->ok();
    }
}
