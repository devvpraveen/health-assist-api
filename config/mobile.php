<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mobile deep-link scheme
    |--------------------------------------------------------------------------
    */
    'scheme' => env('MOBILE_APP_SCHEME', 'healthassist'),

    /*
    |--------------------------------------------------------------------------
    | Associated hosts for universal links / App Links (stubs until deployed)
    |--------------------------------------------------------------------------
    |
    | Comma-separated hostnames in .env, e.g. app.healthassist.test,healthassist.app
    |
    */
    'hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('MOBILE_APP_HOSTS', 'localhost,healthassist.app'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Deep-link path patterns documented for clients
    |--------------------------------------------------------------------------
    */
    'paths' => [
        '/login',
        '/guide',
        '/medications',
        '/appointments/:uuid',
    ],

];
