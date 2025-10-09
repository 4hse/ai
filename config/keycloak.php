<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Keycloak Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL of your Keycloak server.
    |
    */
    'base_url' => env('KEYCLOAK_BASE_URL', 'https://auth.4hse.local'),

    /*
    |--------------------------------------------------------------------------
    | Keycloak Realm
    |--------------------------------------------------------------------------
    |
    | The Keycloak realm name.
    |
    */
    'realm' => env('KEYCLOAK_REALM', '4hse'),

    /*
    |--------------------------------------------------------------------------
    | OAuth2 Client Configuration
    |--------------------------------------------------------------------------
    |
    | Client credentials for the MCP server.
    |
    */
    'client_id' => env('KEYCLOAK_CLIENT_ID', 'mcp-server-4hse'),
    'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | OAuth2 Scopes
    |--------------------------------------------------------------------------
    |
    | Default scopes to request during authorization.
    |
    */
    'scopes' => env('KEYCLOAK_SCOPES', 'openid profile email'),

    /*
    |--------------------------------------------------------------------------
    | Token Verification
    |--------------------------------------------------------------------------
    |
    | Settings for token validation.
    |
    */
    'verify_ssl' => env('KEYCLOAK_VERIFY_SSL', true),

    /*
    |--------------------------------------------------------------------------
    | Endpoints
    |--------------------------------------------------------------------------
    |
    | Keycloak OpenID Connect endpoints (auto-generated from base_url and realm).
    |
    */
    'endpoints' => [
        'authorization' => env('KEYCLOAK_BASE_URL', 'https://auth.4hse.local') . '/realms/' . env('KEYCLOAK_REALM', '4hse') . '/protocol/openid-connect/auth',
        'token' => env('KEYCLOAK_BASE_URL', 'https://auth.4hse.local') . '/realms/' . env('KEYCLOAK_REALM', '4hse') . '/protocol/openid-connect/token',
        'userinfo' => env('KEYCLOAK_BASE_URL', 'https://auth.4hse.local') . '/realms/' . env('KEYCLOAK_REALM', '4hse') . '/protocol/openid-connect/userinfo',
        'introspection' => env('KEYCLOAK_BASE_URL', 'https://auth.4hse.local') . '/realms/' . env('KEYCLOAK_REALM', '4hse') . '/protocol/openid-connect/token/introspect',
        'jwks' => env('KEYCLOAK_BASE_URL', 'https://auth.4hse.local') . '/realms/' . env('KEYCLOAK_REALM', '4hse') . '/protocol/openid-connect/certs',
        'discovery' => env('KEYCLOAK_BASE_URL', 'https://auth.4hse.local') . '/realms/' . env('KEYCLOAK_REALM', '4hse') . '/.well-known/openid-configuration',
    ],
];
