<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default payment gateway
    |--------------------------------------------------------------------------
    |
    | Live Stripe/Razorpay SDKs are out of scope for Phase 6. Manual recording
    | (cash / UPI / card-manual) is the default pluggable gateway.
    |
    */

    'default_gateway' => env('BILLING_DEFAULT_GATEWAY', 'manual'),

    'currency' => env('BILLING_DEFAULT_CURRENCY', 'INR'),

];
