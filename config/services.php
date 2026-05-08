<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Stripe is used for processing payments in SazonRD. Configure your
    | Stripe API keys here. Use test keys for development and live keys
    | for production.
    |
    */

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
        'currency' => env('STRIPE_CURRENCY', 'DOP'),
        'currency_locale' => env('STRIPE_CURRENCY_LOCALE', 'es_DO'),
    ],

    /*
    |--------------------------------------------------------------------------
    | VisitRD SSO Integration
    |--------------------------------------------------------------------------
    |
    | VisitRD SSO allows users to authenticate using their VisitRD account.
    | This integration enables seamless authentication across the VisitRD
    | ecosystem of applications.
    |
    */

    'visitrd' => [
        'client_id' => env('VISITRD_CLIENT_ID'),
        'client_secret' => env('VISITRD_CLIENT_SECRET'),
        'redirect' => env('VISITRD_REDIRECT_URI'),
        'base_url' => env('VISITRD_BASE_URL', 'https://visitrd.com'),
        'api_url' => env('VISITRD_API_URL', 'https://api.visitrd.com'),
        'oauth' => [
            'authorize_url' => env('VISITRD_OAUTH_AUTHORIZE_URL', '/oauth/authorize'),
            'token_url' => env('VISITRD_OAUTH_TOKEN_URL', '/oauth/token'),
            'user_url' => env('VISITRD_OAUTH_USER_URL', '/api/user'),
        ],
        'scopes' => explode(',', env('VISITRD_SCOPES', 'read,profile,email')),
    ],

];
