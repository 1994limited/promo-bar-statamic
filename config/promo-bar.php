<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage driver
    |--------------------------------------------------------------------------
    |
    | Where bars are kept: "file" (YAML files, one per bar, kept with your
    | content and committed with it) or "database" (one table). Move
    | between them with `php please promo-bar:transfer`.
    |
    */

    'driver' => env('PROMO_BAR_DRIVER', 'file'),

    'drivers' => [

        'file' => [
            'directory' => base_path('content/promo-bars'),
        ],

        'database' => [
            'connection' => env('PROMO_BAR_DB_CONNECTION'),
            'table' => 'promo_bars',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Static caching
    |--------------------------------------------------------------------------
    |
    | With Statamic's static caching on, saving a bar clears the pages it
    | shows on, and `promo-bar:tick` (run by the scheduler every minute)
    | clears them when a bar starts or ends. Set "invalidate" to false to
    | clear the cache your own way instead.
    |
    */

    'static_caching' => [
        'invalidate' => true,
        'schedule' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Signed-in cookie
    |--------------------------------------------------------------------------
    |
    | Bars for signed-in visitors (or guests only) are picked in the browser,
    | so they work on statically cached pages. A small cookie, set when a
    | visitor signs in and removed when they sign out, says they're signed
    | in and which user groups they're in. It holds nothing else.
    |
    */

    'audience_cookie' => [
        'name' => 'promo_bar_audience',
        'minutes' => 60 * 24 * 30,
    ],

];
