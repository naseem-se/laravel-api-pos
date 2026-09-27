<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],

    'allowed_methods' => ['*'],

    // Explicit origin list, never '*', since credentials (Bearer
    // tokens) are involved — a wildcard origin with credentialed
    // requests is rejected by browsers anyway, but being explicit
    // here also documents exactly which frontends are trusted.
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:5173'),
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];