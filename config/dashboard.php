<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dashboard Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for dashboard endpoints.
    |
    */

    'cache' => [
        'enabled' => env('DASHBOARD_CACHE_ENABLED', true),

        'durations' => [
            'short' => env('DASHBOARD_CACHE_SHORT', 300),    // 5 minutes
            'normal' => env('DASHBOARD_CACHE_NORMAL', 3600), // 1 hour
            'long' => env('DASHBOARD_CACHE_LONG', 86400),    // 24 hours
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Display Limits
    |--------------------------------------------------------------------------
    |
    | Configure the maximum number of items to display in dashboard widgets.
    |
    */

    'limits' => [
        'low_stock_items' => env('DASHBOARD_LOW_STOCK_LIMIT', 10),
        'employees_by_section' => env('DASHBOARD_EMPLOYEES_BY_SECTION_LIMIT', 10),
        'low_stock_threshold' => env('DASHBOARD_LOW_STOCK_THRESHOLD', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Features
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific dashboard features.
    |
    */

    'features' => [
        'employees' => env('DASHBOARD_FEATURE_EMPLOYEES', true),
        'vacations' => env('DASHBOARD_FEATURE_VACATIONS', true),
        'stock' => env('DASHBOARD_FEATURE_STOCK', true),
        'system' => env('DASHBOARD_FEATURE_SYSTEM', true),
        'activity' => env('DASHBOARD_FEATURE_ACTIVITY', true),
    ],
];
