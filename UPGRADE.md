# Upgrade Guide

## 2.1.0

2.1 is a backward-compatible feature release for Laravel 11–13 and PHP 8.2–8.5. It adds managed streaming, per-request response options, PHP 8.5 CI coverage, and several consistency fixes. There is no new database migration.

### Configuration additions

If you keep a published `config/chatbot.php`, add the following keys manually:

```php
'project' => env('OPENAI_PROJECT'),
'request_timeout' => (int) env('OPENAI_TIMEOUT', 30),
'response_options' => [],
```

Do not publish the config with `--force` unless you are prepared to merge your existing values again.

`response_options` is merged into every managed Responses API request. You may also pass an `$options` array to `createThread()`, `updateThread()`, `createThreadStreamed()`, and `updateThreadStreamed()`. The package always controls `input`, `conversation`, and streaming state.

### Streaming

New conversations:

```php
$stream = ChatBot::createThreadStreamed('Hello', ownerId: auth()->id());

foreach ($stream as $delta) {
    echo $delta;
}

$thread = $stream->thread;
$assistantMessage = $stream->assistantMessage();
```

Existing conversations:

```php
$stream = ChatBot::updateThreadStreamed('Continue', $threadId, auth()->id());

foreach ($stream as $delta) {
    echo $delta;
}
```

Streams are single-use. The assistant message is persisted only after OpenAI emits a successful `response.completed` event and the stream is consumed to completion. The user message remains available for retry or diagnosis when a started stream fails or ends early.

For unprocessed SDK events, use `createResponseStreamedAsRaw()` instead.

### PHP 8.5 and Laravel versions

- Laravel 11: PHP 8.2–8.4
- Laravel 12: PHP 8.2–8.5
- Laravel 13: PHP 8.3–8.5

Laravel 11 is intentionally not advertised on PHP 8.5 because that pairing is outside Laravel's official support range.

Laravel 11 is now a legacy compatibility target because its upstream security support ended in March 2026. Version 2.1 keeps it testable for migration work, but production applications should upgrade to Laravel 12 or 13.

### Behavior fixes to note

- Owner ID `0` now applies an owner filter.
- The default request timeout is now actually 30 seconds. Set `OPENAI_TIMEOUT=0` only if you intentionally want no timeout.
- A failed initial synchronous response removes the partially-created local thread and attempts to delete its remote Conversation.
- Custom Eloquent model names now use `thread_id` explicitly instead of deriving a foreign key from the custom class name.

## 2.0.0

The 2.x line migrates from the **OpenAI Assistants API** (scheduled to shut down on **2026-08-26**) to the **Responses + Conversations API**. This is a breaking change.

### Requirements

- PHP 8.2+
- Laravel 11.29+, 12.12+, or 13.x (Laravel 10 is no longer supported — `openai-php/laravel` ^0.20 does not support it)
- `openai-php/laravel` ^0.20

### Configuration

- **Removed**: `assistant_id`, `sleep_seconds`, `run_max_attempts` (Responses are synchronous — no run polling).
- **Added**: `model` (required, default `gpt-5.4-mini`), `instructions` (optional system prompt), `prompt_id` (optional dashboard Prompt — overrides `model`/`instructions`; note that reusable Prompts are themselves deprecated by OpenAI and will shut down on 2026-11-30).

If you previously set `OPENAI_API_ASSISTANT_ID`, recreate the assistant's `instructions + model` either as a dashboard Prompt (`OPENAI_PROMPT_ID`) or directly via `OPENAI_INSTRUCTIONS` + `OPENAI_MODEL`.

### Database

A new nullable `threads.remote_conversation_id` column is added. The legacy `threads.remote_thread_id` column is **kept** (as the source for the migration command) and will be removed in a future major version. It is **not** backfilled automatically — a `thread_*` id is not a `conv_*` id.

### Run the migration command

```bash
php artisan vendor:publish --tag="chatbot-migrations"   # publish the add_remote_conversation_id migration
php artisan migrate
php artisan chatbot:migrate-to-conversations --dry-run
php artisan chatbot:migrate-to-conversations
```

The command rebuilds each thread from its local `ThreadMessage` transcript (user messages → `input_text`, assistant messages → `output_text`), creates a new Conversation, and stores the new id in `remote_conversation_id`. It is idempotent and supports `--limit`.

> Old `thread_*` remote ids become unreachable after 2026-08-26. Run the migration before then to preserve conversation continuity from your local transcript.

### Removed API surface

- `WaitsForThreadRunCompletion` trait and `HalilCosdu\ChatBot\Exceptions\ThreadRunException` — Responses are synchronous.
- `RawService` Assistants-thread methods (`createThreadAsRaw`, `threadAsRaw`, `messageAsRaw`, `modifyMessageAsRaw`, `listThreadMessagesAsRaw`, `updateThreadAsRaw`, `deleteThreadAsRaw`) — replaced by Conversation/Response methods. See the [README](README.md#raw-openai-access).

### Added API surface

- `ChatBotException` — thrown when a response fails or returns no text.
- Conversation + Response raw methods on the facade (see README).
- `chatbot:migrate-to-conversations` command.
