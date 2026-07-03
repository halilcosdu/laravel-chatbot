# Upgrade Guide

## 2.0.0

The 2.x line migrates from the **OpenAI Assistants API** (shut down on **2026-08-26**) to the **Responses + Conversations API**. This is a breaking change.

### Requirements

- PHP 8.2+
- Laravel 11.29+, 12.12+, or 13.x (Laravel 10 is no longer supported — `openai-php/laravel` ^0.20 does not support it)
- `openai-php/laravel` ^0.20

### Configuration

- **Removed**: `assistant_id`, `sleep_seconds`, `run_max_attempts` (Responses are synchronous — no run polling).
- **Added**: `model` (required, default `gpt-5.4-mini`), `instructions` (optional system prompt), `prompt_id` (optional dashboard Prompt — overrides `model`/`instructions`; note that reusable Prompts are themselves deprecated by OpenAI and shut down on 2026-11-30).

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
