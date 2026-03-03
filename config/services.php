<?php

/**
 * API = backend at API_BASE_URL (login, register, recipes, cuisines). /api/* proxy forwards to it.
 */

return [
    'api' => [
        'base_url' => env('API_BASE_URL', ''),
        'verify_ssl' => env('API_VERIFY_SSL', false),
    ],
];
