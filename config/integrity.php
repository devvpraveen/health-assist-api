<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Blockchain record integrity verification
    |--------------------------------------------------------------------------
    |
    | Blockchain attestation is deferred. Keep disabled unless a real provider
    | is configured and explicitly enabled.
    |
    */

    'blockchain_verification_enabled' => env('BLOCKCHAIN_VERIFICATION_ENABLED', false),

    'blockchain_provider' => env('BLOCKCHAIN_PROVIDER') ?: null,

];
