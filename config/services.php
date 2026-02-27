<?php

/**
 * Naming: "API" = external backend at API_BASE_URL. "api" = internal Foodbook proxy routes (/api/*).
 */

return [
    'api' => [
        // API (API_BASE_URL): where outbound requests (login, register, recipes, cuisines) are sent.
        'base_url' => env('API_BASE_URL', ''),
        'verify_ssl' => env('API_VERIFY_SSL', false),
        // When outbound requests to the API loop back to this app, proxy forwards to the API here.
        'internal_base_url' => env('API_INTERNAL_BASE_URL', ''),
    ],
];
