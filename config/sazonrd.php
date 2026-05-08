<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SazónRD Configuration
    |--------------------------------------------------------------------------
    */

    // Información del sitio
    'name' => env('APP_NAME', 'SazónRD'),
    'tagline' => 'El sabor de República Dominicana en tu puerta',
    'support_email' => env('SUPPORT_EMAIL', 'soporte@sazonrd.com'),
    'support_phone' => env('SUPPORT_PHONE', '+1 809-555-0000'),

    /*
    |--------------------------------------------------------------------------
    | Delivery Configuration
    |--------------------------------------------------------------------------
    */
    'delivery' => [
        'base_fee' => env('DELIVERY_BASE_FEE', 100), // RD$
        'per_km_fee' => env('DELIVERY_PER_KM_FEE', 25), // RD$ por km adicional
        'free_threshold' => env('DELIVERY_FREE_THRESHOLD', 1500), // Pedido mínimo para envío gratis
        'max_distance_km' => env('MAX_DELIVERY_DISTANCE_KM', 15),
        'base_km_included' => 3, // Primeros km incluidos en tarifa base
    ],

    /*
    |--------------------------------------------------------------------------
    | Order Configuration
    |--------------------------------------------------------------------------
    */
    'orders' => [
        'tax_rate' => 0.18, // ITBIS 18%
        'service_fee_rate' => 0.05, // 5% de servicio
        'min_order_amount' => 200, // Pedido mínimo RD$
        'max_scheduled_days' => 7, // Máximo días para programar pedido
        'cancel_window_minutes' => 5, // Minutos para cancelar sin penalidad
    ],

    /*
    |--------------------------------------------------------------------------
    | Loyalty Program
    |--------------------------------------------------------------------------
    */
    'loyalty' => [
        'points_per_currency' => 100, // 1 punto por cada RD$100
        'points_expiry_days' => 365, // Puntos expiran en 1 año
        'redemption_rate' => 10, // 10 puntos = RD$1
    ],

    /*
    |--------------------------------------------------------------------------
    | Commission Configuration
    |--------------------------------------------------------------------------
    */
    'commission' => [
        'default_rate' => 15, // 15% comisión por defecto
        'featured_rate' => 12, // 12% para restaurantes destacados
        'premium_rate' => 10, // 10% para restaurantes premium
    ],

    /*
    |--------------------------------------------------------------------------
    | SSO with visitRD
    |--------------------------------------------------------------------------
    */
    'visitrd' => [
        'url' => env('VISITRD_SSO_URL', 'https://visitrepublicadominicana.com'),
        'client_id' => env('VISITRD_SSO_CLIENT_ID'),
        'client_secret' => env('VISITRD_SSO_CLIENT_SECRET'),
        'api_key' => env('VISITRD_API_KEY'),
        'sync_endpoint' => '/api/v1/sazonrd/sync',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'new_order_sound' => true,
        'order_update_push' => true,
        'promotion_email' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Settings
    |--------------------------------------------------------------------------
    */
    'images' => [
        'restaurant_logo' => [
            'width' => 400,
            'height' => 400,
            'quality' => 90,
        ],
        'restaurant_cover' => [
            'width' => 1200,
            'height' => 675,
            'quality' => 85,
        ],
        'dish' => [
            'width' => 800,
            'height' => 600,
            'quality' => 85,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Operating Hours
    |--------------------------------------------------------------------------
    */
    'operating_hours' => [
        'default_open' => '08:00',
        'default_close' => '22:00',
        'timezone' => 'America/Santo_Domingo',
    ],
];
