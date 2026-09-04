<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Active provider driver
    |--------------------------------------------------------------------------
    | The assistant is provider-agnostic. The default "offline" driver is a
    | deterministic, fully self-contained retrieval-augmented responder that
    | requires no external API keys and is safe for tests, demos and offline
    | environments. Set ASSISTANT_DRIVER=openai (and OPENAI_API_KEY) to route
    | generation/embeddings through OpenAI without any code changes.
    */
    'driver' => env('ASSISTANT_DRIVER', 'offline'),

    'branding' => [
        'name' => env('ASSISTANT_NAME', 'GIPA Assistant'),
        'tagline' => env('ASSISTANT_TAGLINE', 'Ask about investment opportunities, sectors, districts and onboarding.'),
    ],

    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-4o-mini'),
            'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
            'timeout' => (int) env('OPENAI_TIMEOUT', 30),
        ],
    ],

    'embedding' => [
        // Dimensionality used by the deterministic offline embedding model.
        'dimensions' => (int) env('ASSISTANT_EMBEDDING_DIMENSIONS', 256),
    ],

    'retrieval' => [
        'chunk_size' => (int) env('ASSISTANT_CHUNK_SIZE', 900),
        'chunk_overlap' => (int) env('ASSISTANT_CHUNK_OVERLAP', 150),
        'max_chunks' => (int) env('ASSISTANT_MAX_CHUNKS', 5),
        'min_score' => (float) env('ASSISTANT_MIN_SCORE', 0.08),
    ],

    'conversation' => [
        'max_history_messages' => (int) env('ASSISTANT_MAX_HISTORY', 10),
        'retention_days' => (int) env('ASSISTANT_RETENTION_DAYS', 90),
    ],

    'guardrails' => [
        'max_question_length' => (int) env('ASSISTANT_MAX_QUESTION_LENGTH', 1000),
        'system_prompt' => 'You are the GIPA Assistant, a knowledgeable institutional assistant for the '
            .'Ghana Investment Promotion Authority (GIPA) Investment Opportunities Mapping Platform. '
            .'Answer only from the verified platform data and knowledge base provided to you. '
            .'Never invent fees, contacts, policies, statistics or opportunity details. '
            .'Always cite your sources. If the information is not available, say so plainly and '
            .'direct the user to official GIPA channels.',
        'refusal_message' => "I don't have verified information on that yet. For anything outside "
            .'the published investment opportunities, sectors, districts, investor onboarding and '
            .'certificate verification I cover, please contact the Ghana Investment Promotion Authority '
            .'directly so an officer can assist you.',
        'injection_deflection' => 'I can only help with questions about GIPA investment opportunities, '
            .'sectors, districts, investor onboarding and certificate verification. How can I help you '
            .'with any of those?',
    ],

    'rate_limit' => [
        'per_minute' => (int) env('ASSISTANT_RATE_PER_MINUTE', 15),
    ],
];
