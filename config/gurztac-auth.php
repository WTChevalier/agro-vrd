<?php

return [
    /*
    |--------------------------------------------------------------------------
    | URL del Hub IdP Cuenta Gurztac
    |--------------------------------------------------------------------------
    | Endpoint base donde vive el IdP. Las apps tenant validan tokens contra él.
    */
    'hub_url' => env('GURZTAC_HUB_URL', 'https://corporativo.gurztacproductions.com'),

    /*
    |--------------------------------------------------------------------------
    | Endpoint JWKS (clave pública para verificar JWT)
    |--------------------------------------------------------------------------
    | El IdP publica la clave pública en /api/idp/auth/jwks. La cacheamos N
    | minutos para evitar HTTP cada request.
    */
    'jwks_url' => env('GURZTAC_JWKS_URL', 'https://corporativo.gurztacproductions.com/api/idp/auth/jwks'),
    'jwks_cache_ttl_minutes' => env('GURZTAC_JWKS_CACHE_TTL', 60),

    /*
    |--------------------------------------------------------------------------
    | Audiencia esperada del JWT (claim "aud")
    |--------------------------------------------------------------------------
    | Cada app tenant declara su propio aud_id (slug en plataformas).
    | El IdP emite tokens con aud = slug de la plataforma.
    */
    'aud' => env('GURZTAC_AUD', null),

    /*
    |--------------------------------------------------------------------------
    | Issuer esperado del JWT (claim "iss")
    |--------------------------------------------------------------------------
    */
    'iss' => env('GURZTAC_ISS', 'cuenta.gurztacproductions.com'),

    /*
    |--------------------------------------------------------------------------
    | Algoritmo de firma del JWT
    |--------------------------------------------------------------------------
    | Por seguridad, el package SOLO acepta RS256. NO permitir HS256 ni "none".
    */
    'algorithm' => 'RS256',

    /*
    |--------------------------------------------------------------------------
    | Tolerancia de tiempo (clock skew)
    |--------------------------------------------------------------------------
    | Segundos de gracia entre clocks. 60s default.
    */
    'clock_skew_seconds' => 60,

    /*
    |--------------------------------------------------------------------------
    | SSO endpoints exportados a la app tenant
    |--------------------------------------------------------------------------
    | El package agrega rutas web que redirigen al Hub y reciben callbacks.
    */
    'sso' => [
        'enabled' => env('GURZTAC_SSO_ENABLED', true),
        'login_path' => '/auth/sso/login',
        'callback_path' => '/auth/sso/callback',
        'logout_path' => '/auth/sso/logout',
        'after_login_redirect' => env('GURZTAC_AFTER_LOGIN', '/'),
        'after_logout_redirect' => env('GURZTAC_AFTER_LOGOUT', '/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage del token en la app tenant
    |--------------------------------------------------------------------------
    | "session" guarda JWT en session() (default). "cookie" httponly secure.
    */
    'token_storage' => env('GURZTAC_TOKEN_STORAGE', 'session'),
    'cookie_name' => 'gurztac_jwt',
    'cookie_lifetime_minutes' => 60 * 24, // 24h

    /*
    |--------------------------------------------------------------------------
    | Auto-creación de user local
    |--------------------------------------------------------------------------
    | Cuando un user valida JWT por primera vez en una app, el package puede
    | crear automáticamente un registro local en la tabla users de la app.
    */
    'auto_provision_local_user' => env('GURZTAC_AUTO_PROVISION', true),
    'local_user_model' => env('GURZTAC_LOCAL_USER_MODEL', \App\Models\User::class),
    'sub_claim_field' => 'gurztac_user_id', // columna en users locales que guarda user_ecosistema_id del Hub
];
