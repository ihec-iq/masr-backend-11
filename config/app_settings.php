<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Settings
    |--------------------------------------------------------------------------
    |
    | Custom application settings for MSAR ERP system
    |
    */

    'maintenance_mode' => env('MAINTENANCE_MODE', false),

    'rate_limiting' => [
        'api' => [
            'max_attempts' => env('RATE_LIMIT_PER_MINUTE', 60),
            'decay_minutes' => 1,
        ],
        'strict' => [
            'max_attempts' => env('RATE_LIMIT_STRICT', 10),
            'decay_minutes' => 1,
        ],
    ],

    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],

];
