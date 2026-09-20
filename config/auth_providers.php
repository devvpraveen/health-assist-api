<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Progressive auth providers (Phase 1: development stubs)
    |--------------------------------------------------------------------------
    */

    'driver' => env('AUTH_PROVIDERS_DRIVER', 'dev'),

    'dev_otp_code' => env('DEV_OTP_CODE', '123456'),

    'otp_ttl_minutes' => (int) env('AUTH_OTP_TTL_MINUTES', 10),

    'otp_max_attempts' => (int) env('AUTH_OTP_MAX_ATTEMPTS', 5),

    'google' => [
        'stub_allowed' => (bool) env('AUTH_GOOGLE_STUB_ALLOWED', true),
    ],

];
