<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp gateway driver
    |--------------------------------------------------------------------------
    |
    | evolution — Evolution API (https://github.com/evolution-foundation/evolution-api)
    | null — no-op gateway (tests / disabled)
    |
    | When driver is evolution but EVOLUTION_BASE_URL or EVOLUTION_API_KEY is
    | empty, the NullWhatsAppGateway is bound instead.
    |
    */

    'driver' => env('WHATSAPP_DRIVER', 'evolution'),

    'reminders_enabled' => (bool) env('WHATSAPP_REMINDERS_ENABLED', true),

    'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET'),

    'process_sync' => (bool) env('WHATSAPP_PROCESS_SYNC', false),

    'evolution' => [
        'base_url' => env('EVOLUTION_BASE_URL', 'http://localhost:8080'),
        'api_key' => env('EVOLUTION_API_KEY'),
        'default_instance' => env('EVOLUTION_DEFAULT_INSTANCE'),
        'timeout' => (int) env('EVOLUTION_HTTP_TIMEOUT', 15),
    ],

    'handoff_phrases' => [
        'talk to a person',
        'talk to person',
        'talk to human',
        'human please',
        'real person',
        'speak to agent',
        'speak to a human',
        'customer service',
        'agent',
        'human',
        'insan se baat',
        'agent se baat',
        'kisi se baat',
        'operator',
    ],

    'handoff_acknowledgement' => 'Connecting you with a team member. Someone will reply here shortly.',

    'default_reminder_template' => 'Health Assist reminder: you have an appointment on {starts_at}. Please arrive a few minutes early.',

    'webhook_events' => [
        'MESSAGES_UPSERT',
        'CONNECTION_UPDATE',
    ],

];
