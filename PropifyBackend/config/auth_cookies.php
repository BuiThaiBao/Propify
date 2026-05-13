<?php

return [
    'domain' => env('AUTH_COOKIE_DOMAIN'),
    'path' => env('AUTH_COOKIE_PATH', '/'),
    'secure' => env('AUTH_COOKIE_SECURE', env('APP_ENV') === 'production'),
    'same_site' => env('AUTH_COOKIE_SAME_SITE', 'Lax'),

    'user' => [
        'access_cookie' => env('AUTH_ACCESS_COOKIE', 'propify_user_access_token'),
        'refresh_cookie' => env('AUTH_REFRESH_COOKIE', 'propify_user_refresh_token'),
    ],

    'admin' => [
        'access_cookie' => env('ADMIN_AUTH_ACCESS_COOKIE', 'propify_admin_access_token'),
        'refresh_cookie' => env('ADMIN_AUTH_REFRESH_COOKIE', 'propify_admin_refresh_token'),
    ],
];
