<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Analytics
    |--------------------------------------------------------------------------
    |
    | Drivers fan out via AnalyticsManager. Default is log (no network).
    | Live GA4 / Mixpanel / Firebase calls must be Http::fake'd in tests.
    |
    */

    'analytics' => [
        'enabled' => (bool) env('MARKETING_ANALYTICS_ENABLED', true),
        'drivers' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('MARKETING_ANALYTICS_DRIVERS', 'log'))
        ))),
        'user_key_salt' => env('MARKETING_ANALYTICS_USER_KEY_SALT', 'healthassist-marketing'),
        'allowed_property_keys' => [
            'page',
            'path',
            'locale',
            'referrer',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_content',
            'utm_term',
            'entity_type',
            'entity_id',
            'experiment_key',
            'variant',
            'referral_code',
            'lead_id',
        ],
        'forbidden_property_key_pattern' => '/symptom|diagnos|medication|phi|report|chat/i',
    ],

    'ga4' => [
        'measurement_id' => env('GA4_MEASUREMENT_ID', ''),
        'api_secret' => env('GA4_API_SECRET', ''),
        'endpoint' => env('GA4_MP_ENDPOINT', 'https://www.google-analytics.com/mp/collect'),
    ],

    'mixpanel' => [
        'token' => env('MIXPANEL_TOKEN', ''),
        'endpoint' => env('MIXPANEL_ENDPOINT', 'https://api.mixpanel.com/track'),
    ],

    'firebase' => [
        'app_id' => env('FIREBASE_APP_ID', ''),
    ],

    'public_site_url' => env('MARKETING_PUBLIC_SITE_URL', env('APP_URL', 'http://localhost')),

];
