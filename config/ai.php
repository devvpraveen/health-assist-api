<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI provider
    |--------------------------------------------------------------------------
    |
    | Use "mock" for local/testing (never hits the network). Set
    | "openai_compatible" only when AI_OPENAI_API_KEY is present.
    |
    */

    'default_provider' => env('AI_PROVIDER', 'mock'),

    'providers' => [
        'mock' => [
            'driver' => 'mock',
        ],
        'openai_compatible' => [
            'driver' => 'openai_compatible',
            'base_url' => env('AI_OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'api_key' => env('AI_OPENAI_API_KEY'),
            'default_model' => env('AI_OPENAI_DEFAULT_MODEL', 'gpt-4o-mini'),
            'timeout' => (int) env('AI_OPENAI_TIMEOUT', 30),
        ],
        'groq' => [
            'driver' => 'groq',
            'base_url' => env('AI_GROQ_BASE_URL', 'https://api.groq.com/v1'),
            'api_key' => env('AI_GROQ_API_KEY'),
            'default_model' => env('AI_GROQ_DEFAULT_MODEL', 'groq/llama-3.1-8b-instant'),
            'timeout' => (int) env('AI_GROQ_TIMEOUT', 30),
        ],
        'anthropic' => [
            'driver' => 'anthropic',
            'base_url' => env('AI_ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
            'api_key' => env('AI_ANTHROPIC_API_KEY'),
            'default_model' => env('AI_ANTHROPIC_DEFAULT_MODEL', 'claude-3-5-sonnet-20260319'),
            'timeout' => (int) env('AI_ANTHROPIC_TIMEOUT', 30),
        ],
        'gemini' => [
            'driver' => 'gemini',
            'base_url' => env('AI_GEMINI_BASE_URL', 'https://api.gemini.com/v1'),
            'api_key' => env('AI_GEMINI_API_KEY'),
            'default_model' => env('AI_GEMINI_DEFAULT_MODEL', 'gemini-2.5-flash'),
            'timeout' => (int) env('AI_GEMINI_TIMEOUT', 30),
        ],
        'moonshot' => [
            'driver' => 'moonshot',
            'base_url' => env('AI_MOONSHOT_BASE_URL', 'https://api.moonshot.ai/v1'),
            'api_key' => env('AI_MOONSHOT_API_KEY'),
            'default_model' => env('AI_MOONSHOT_DEFAULT_MODEL', 'moonshot/llama-3.1-8b-instant'),
            'timeout' => (int) env('AI_MOONSHOT_TIMEOUT', 30),
        ],
        'openrouter' => [
            'driver' => 'openrouter',
            'base_url' => env('AI_OPENROUTER_BASE_URL', 'https://api.openrouter.ai/v1'),
            'api_key' => env('AI_OPENROUTER_API_KEY'),
            'default_model' => env('AI_OPENROUTER_DEFAULT_MODEL', 'openai/gpt-4o-mini'),
            'timeout' => (int) env('AI_OPENROUTER_TIMEOUT', 30),
        ],
        'ollama' => [
            'driver' => 'ollama',
            'base_url' => env('AI_OLLAMA_BASE_URL', 'http://localhost:11434'),
            'api_key' => env('AI_OLLAMA_API_KEY'),
            'default_model' => env('AI_OLLAMA_DEFAULT_MODEL', 'llama3.1:8b'),
            'timeout' => (int) env('AI_OLLAMA_TIMEOUT', 30),
        ],
        'deepseek' => [
            'driver' => 'deepseek',
            'base_url' => env('AI_DEEPSEEK_BASE_URL', 'https://api.deepseek.com/v1'),
            'api_key' => env('AI_DEEPSEEK_API_KEY'),
            'default_model' => env('AI_DEEPSEEK_DEFAULT_MODEL', 'deepseek-chat'),
            'timeout' => (int) env('AI_DEEPSEEK_TIMEOUT', 30),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Task → model defaults (overridable by DB registry + tenant settings)
    |--------------------------------------------------------------------------
    |
    | When AI_PROVIDER=openai_compatible, defaults point at the live model id
    | so ModelRouter does not send "mock-chat" to a real vendor API.
    |
    */

    'task_models' => (static function (): array {
        $provider = env('AI_PROVIDER', 'mock');
        $live = $provider === 'openai_compatible';
        $chatModel = $live
            ? env('AI_OPENAI_DEFAULT_MODEL', 'gpt-4o-mini')
            : 'mock-chat';
        $classifyModel = $live
            ? env('AI_OPENAI_DEFAULT_MODEL', 'gpt-4o-mini')
            : 'mock-classify';
        $embedModel = $live
            ? env('AI_OPENAI_DEFAULT_MODEL', 'gpt-4o-mini')
            : 'mock-embed';

        $chat = [
            'provider' => $provider,
            'model' => $chatModel,
            'model_version' => '1',
        ];

        return [
            'general_conversation' => $chat,
            'classification' => [
                'provider' => $provider,
                'model' => $classifyModel,
                'model_version' => '1',
            ],
            'medical_document' => $chat,
            'clinical_documentation' => $chat,
            'embeddings' => [
                'provider' => $provider,
                'model' => $embedModel,
                'model_version' => '1',
            ],
            'speech' => $chat,
            'marketing' => $chat,
        ];
    })(),

    /*
    |--------------------------------------------------------------------------
    | Optional env-driven custom model aliases (empty by default)
    |--------------------------------------------------------------------------
    |
    | Example:
    | 'my-clinic-chat' => [
    |     'provider' => 'openai_compatible',
    |     'model' => 'gpt-4o-mini',
    |     'model_version' => '1',
    |     'task_types' => ['general_conversation'],
    |     'config' => ['temperature' => 0.3],
    | ],
    |
    | Secrets (API keys) stay in env / provider secrets — never in model config.
    |
    */

    'custom_models' => [
        //
    ],

    'generation_defaults' => [
        'temperature' => (float) env('AI_TEMPERATURE', 0.2),
        'max_tokens' => (int) env('AI_MAX_TOKENS', 2048),
    ],

    'allowed_task_types' => [
        'general_conversation',
        'classification',
        'medical_document',
        'clinical_documentation',
        'embeddings',
        'speech',
        'marketing',
    ],

    'allowed_drivers' => [
        'mock',
        'openai_compatible',
    ],

    'metering' => [
        'enabled' => env('AI_METERING_ENABLED', true),
        'enforce_quotas' => env('AI_ENFORCE_QUOTAS', true),
        // null / empty = unlimited
        'monthly_request_limit' => env('AI_MONTHLY_REQUEST_LIMIT', 5000),
        'monthly_token_limit' => env('AI_MONTHLY_TOKEN_LIMIT', 2000000),
        'pricing' => [
            'default_input_per_1m_cents' => (float) env('AI_COST_INPUT_PER_1M_CENTS', 15),
            'default_output_per_1m_cents' => (float) env('AI_COST_OUTPUT_PER_1M_CENTS', 60),
            'models' => [
                // Override per external model id when needed.
                // 'gpt-4o-mini' => ['input_per_1m_cents' => 15, 'output_per_1m_cents' => 60],
            ],
        ],
    ],

    'audit' => [
        'store_redacted_preview_chars' => (int) env('AI_AUDIT_PREVIEW_CHARS', 200),
    ],

    'disclaimer' => 'AI is assistive only; not a diagnosis. A qualified clinician must review clinical decisions.',

    /*
    |--------------------------------------------------------------------------
    | Offline training worker (never updates live agent weights)
    |--------------------------------------------------------------------------
    */

    'training' => [
        'driver' => env('AI_TRAINING_DRIVER', 'mock'),
        'disk' => env('AI_TRAINING_DISK', 'local'),
        'python_binary' => env('AI_TRAINING_PYTHON', 'python3'),
        'python_script' => env('AI_TRAINING_SCRIPT', dirname(__DIR__, 2).'/training/worker.py'),
        'timeout_seconds' => (int) env('AI_TRAINING_TIMEOUT', 120),
        'base_model_key' => env('AI_TRAINING_BASE_MODEL', 'mock-chat'),
        'require_deidentified' => env('AI_TRAINING_REQUIRE_DEIDENTIFIED', true),
    ],

];
