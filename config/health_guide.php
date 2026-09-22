<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Provider ranking weights (deterministic / auditable)
    |--------------------------------------------------------------------------
    |
    | Scores are computed only by ProviderRankingService. The Health Guide AI
    | may explain results but must never re-sort or mutate these scores.
    |
    | Limitations: distance, price suitability, and review signals are stubbed
    | at 0 until geo/pricing/reviews data is available.
    |
    */

    'ranking' => [
        'weights' => [
            'specialty_match' => 40,
            'experience' => 15,
            'verification_status' => 15,
            'is_public' => 10,
            'availability' => 15,
            'language_match' => 5,
            'distance' => 0,
            'price' => 0,
            'reviews' => 0,
        ],
        'max_experience_years' => 30,
        'max_recommendations' => 5,
        'next_slots_limit' => 3,
        'next_slots_days' => 7,
    ],

    'disclaimer' => 'Health Assist AI is assistive only and does not diagnose or prescribe. Seek emergency care for life-threatening symptoms. A qualified clinician must review clinical decisions.',

    'guest' => [
        'max_user_turns' => (int) env('HEALTH_GUIDE_GUEST_MAX_TURNS', 3),
        'ttl_hours' => (int) env('HEALTH_GUIDE_GUEST_TTL_HOURS', 24),
        'default_tenant_slug' => env('HEALTH_GUIDE_GUEST_TENANT_SLUG', 'healthassist-demo'),
        'opening_message' => 'Have a health question? Ask Health Assist in plain language. This is assistance only — not a diagnosis or emergency care.',
        'continue_message' => 'Thanks. I have a better understanding of what you\'re experiencing. I can help with next steps, including finding an appropriate healthcare professional. Create your free Health Assist account to continue and save this conversation.',
        'auth_required_message' => 'Please continue with Health Assist to save this conversation and keep going.',
    ],

    'escalation' => [
        'emergency' => 'Based on what you shared, please seek emergency medical care now (call local emergency services). Health Assist cannot handle emergencies.',
        'urgent' => 'Your symptoms may need urgent clinical attention. Please contact a clinician or urgent care promptly. Provider booking through Health Assist is paused for safety.',
        'clinician_review' => 'A clinician should review your symptoms. I can still help you find an appropriate provider.',
    ],

    'insufficient_information_message' => 'I need a bit more detail about your symptoms (what hurts, how long, and how severe) before recommending care.',

];
