<?php

use Monolog\Handler\RotatingFileHandler;

return [
    'default' => env('LOG_CHANNEL', 'stack'),
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'daily')),
            'ignore_exceptions' => false,
        ],
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'daily' => [
            'driver' => 'monolog',
            'handler' => RotatingFileHandler::class,
            'level' => env('LOG_LEVEL', 'debug'),
            'handler_with' => [
                'filename' => storage_path('logs/.log'),
                'maxFiles' => (int) env('LOG_DAILY_DAYS', 14),
                'filenameFormat' => '{date}',
                'dateFormat' => 'Y-m-d',
            ],
        ],
    ],
];
