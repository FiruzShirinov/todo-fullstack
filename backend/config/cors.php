<?php

return [

    /*
     * We use Sanctum bearer tokens (stateless), so credentialed cookies are not
     * required. The frontend origin is driven by FRONTEND_URL for clarity.
     */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
