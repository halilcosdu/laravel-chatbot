<?php

// config for HalilCosdu/ChatBot

return [
    'assistant_id' => env('OPENAI_API_ASSISTANT_ID'),
    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),
    'request_timeout' => env('OPENAI_TIMEOUT'),
    'sleep_seconds' => env('OPENAI_SLEEP_SECONDS'),
    'models' => [
        'thread' => \HalilCosdu\ChatBot\Models\Thread::class,
    ],
];
