<?php

use HalilCosdu\ChatBot\Models\Thread;
use HalilCosdu\ChatBot\Models\ThreadMessage;

// config for HalilCosdu/ChatBot

return [
    /*
    | The OpenAI model used for responses. Required.
    */
    'model' => env('OPENAI_MODEL', 'gpt-5.4-mini'),

    /*
    | Optional system instructions forwarded to every response.
    | This replaces the old `assistant_id` (Assistants API) concept.
    */
    'instructions' => env('OPENAI_INSTRUCTIONS'),

    /*
    | Optional dashboard Prompt id. If set, it is forwarded to the Responses
    | API as `prompt` and overrides `model`/`instructions`.
    |
    | NOTE: reusable prompts are themselves deprecated by OpenAI and shut down
    | on 2026-11-30. Prefer `model` + `instructions` for new integrations.
    */
    'prompt_id' => env('OPENAI_PROMPT_ID'),

    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),
    'request_timeout' => env('OPENAI_TIMEOUT'),

    'models' => [
        'thread' => env('CHATBOT_THREAD_MODEL', Thread::class),
        'thread_messages' => env('CHATBOT_THREAD_MESSAGE_MODEL', ThreadMessage::class),
    ],
];
