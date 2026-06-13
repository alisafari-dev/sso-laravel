<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Keycloak connection
    |--------------------------------------------------------------------------
    |
    | These values are merged into config('services.keycloak') for Socialite.
    |
    */

    'keycloak' => [
        'client_id' => env('KEYCLOAK_CLIENT_ID'),
        'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
        'redirect' => env('KEYCLOAK_REDIRECT_URI'),
        'post_logout_redirect' => env('KEYCLOAK_POST_LOGOUT_REDIRECT_URI'),
        'base_url' => env('KEYCLOAK_BASE_URL'),
        'realms' => env('KEYCLOAK_REALM', 'master'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth scopes
    |--------------------------------------------------------------------------
    */

    'scopes' => ['openid', 'profile', 'email'],

    /*
    |--------------------------------------------------------------------------
    | Local user mapping
    |--------------------------------------------------------------------------
    */

    'user' => [
        'model' => env('KEYCLOAK_SSO_USER_MODEL', 'App\\Models\\User'),
        'identifier_column' => env('KEYCLOAK_SSO_USER_IDENTIFIER_COLUMN', 'personal_id'),
        'identifier_claim' => env('KEYCLOAK_SSO_USER_IDENTIFIER_CLAIM', 'employeeID'),
        'placeholder_email_domain' => env('KEYCLOAK_SSO_PLACEHOLDER_EMAIL_DOMAIN', 'sso.local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | All SSO routes are registered under a prefix so they do not collide
    | with existing application or service routes.
    |
    */

    'routes' => [
        'enabled' => env('KEYCLOAK_SSO_ROUTES_ENABLED', true),
        'prefix' => env('KEYCLOAK_SSO_ROUTE_PREFIX', 'sso'),
        'middleware' => ['web'],
        'guest_middleware' => ['guest'],
        'auth_middleware' => ['auth'],
        'paths' => [
            'login' => 'login',
            'redirect' => 'auth/keycloak',
            'callback' => 'callback',
            'logout' => 'logout',
        ],
        'names' => [
            'login' => 'keycloak-sso.login',
            'redirect' => 'keycloak-sso.redirect',
            'callback' => 'keycloak-sso.callback',
            'logout' => 'keycloak-sso.logout',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirects
    |--------------------------------------------------------------------------
    */

    'redirect_after_login' => env('KEYCLOAK_SSO_REDIRECT_AFTER_LOGIN', 'dashboard'),

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    */

    'views' => [
        'login' => env('KEYCLOAK_SSO_LOGIN_VIEW', 'keycloak-sso::login'),
    ],

];
