# Laravel Chatbot

[![Latest Version on Packagist](https://img.shields.io/packagist/v/halilcosdu/laravel-chatbot.svg?style=flat-square)](https://packagist.org/packages/halilcosdu/laravel-chatbot)
[![Compatibility](https://img.shields.io/github/actions/workflow/status/halilcosdu/laravel-chatbot/run-tests.yml?branch=main&label=compatibility&style=flat-square)](https://github.com/halilcosdu/laravel-chatbot/actions/workflows/run-tests.yml)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/halilcosdu/laravel-chatbot/phpstan.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/halilcosdu/laravel-chatbot/actions/workflows/phpstan.yml)
[![Code Style](https://img.shields.io/github/actions/workflow/status/halilcosdu/laravel-chatbot/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/halilcosdu/laravel-chatbot/actions/workflows/fix-php-code-style-issues.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/halilcosdu/laravel-chatbot.svg?style=flat-square)](https://packagist.org/packages/halilcosdu/laravel-chatbot)
[![License](https://img.shields.io/packagist/l/halilcosdu/laravel-chatbot.svg?style=flat-square)](LICENSE.md)

A Laravel-native, stateful chatbot built on OpenAI's **Responses** and **Conversations** APIs. It manages the remote conversation, keeps a local Eloquent transcript, scopes threads to your users, and supports both synchronous and streamed responses through one facade.

Version 2.x does not use the deprecated Assistants API. If you are upgrading from 1.x, read the [upgrade guide](UPGRADE.md) and migrate before the Assistants API shuts down on August 26, 2026.

## What you get

- Responses + Conversations API integration through `openai-php/laravel`.
- Local `Thread` and `ThreadMessage` models with configurable model classes.
- Create, continue, search, paginate, inspect, and delete conversations.
- Single-use text streams for both new and existing threads.
- Automatic transcript persistence after a response completes successfully.
- Per-request Responses API options without allowing callers to replace managed input or conversation state.
- Optional owner scoping for multi-user applications.
- Raw Conversation and Response methods when you need the underlying SDK objects.
- An idempotent command for migrating 1.x Assistants threads from their local transcript.

## Compatibility

| Laravel | Supported PHP versions | Testbench |
| --- | --- | --- |
| 11.x (`11.29+`, legacy) | 8.2, 8.3, 8.4 | 9.x |
| 12.x (`12.12+`) | 8.2, 8.3, 8.4, **8.5** | 10.x |
| 13.x | 8.3, 8.4, **8.5** | 11.x |

The CI matrix tests every compatible combination above with both lowest and stable dependency sets. Laravel 11 reached upstream security end-of-life on March 12, 2026. Version 2.1 retains Laravel 11 runtime compatibility for migrations, but production applications should upgrade to Laravel 12 or 13. The Laravel 11 CI jobs use Composer's `--no-blocking` option solely to resolve that end-of-life framework; the package does not weaken a consuming application's Composer security policy.

Laravel 11 is not listed with PHP 8.5 because [Laravel's support policy](https://laravel.com/docs/13.x/releases#support-policy) ends its PHP range at 8.4; use Laravel 12 or 13 when deploying on PHP 8.5.

Additional requirements:

- `openai-php/laravel` `^0.20`
- An OpenAI API key
- A database supported by your Laravel version

## Installation

Install the package with Composer:

```bash
composer require halilcosdu/laravel-chatbot
```

Publish its configuration and migrations, then migrate:

```bash
php artisan vendor:publish --tag=chatbot-config
php artisan vendor:publish --tag=chatbot-migrations
php artisan migrate
```

Laravel discovers the service provider and `ChatBot` facade automatically.

### Environment

At minimum, configure your API key:

```dotenv
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-5.4-mini
OPENAI_TIMEOUT=30
```

Optional values:

```dotenv
OPENAI_INSTRUCTIONS="You are a concise support assistant."
OPENAI_ORGANIZATION=
OPENAI_PROJECT=
```

`OPENAI_PROMPT_ID` remains available for applications already using reusable prompt objects. OpenAI's [deprecation schedule](https://developers.openai.com/api/docs/deprecations) says reusable prompts will shut down on November 30, 2026, so new integrations should keep instructions in application configuration instead.

### Configuration

The published `config/chatbot.php` contains:

```php
use HalilCosdu\ChatBot\Models\Thread;
use HalilCosdu\ChatBot\Models\ThreadMessage;

return [
    'model' => env('OPENAI_MODEL', 'gpt-5.4-mini'),
    'instructions' => env('OPENAI_INSTRUCTIONS'),
    'prompt_id' => env('OPENAI_PROMPT_ID'),

    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),
    'project' => env('OPENAI_PROJECT'),
    'request_timeout' => (int) env('OPENAI_TIMEOUT', 30),

    'response_options' => [],

    'models' => [
        'thread' => env('CHATBOT_THREAD_MODEL', Thread::class),
        'thread_messages' => env('CHATBOT_THREAD_MESSAGE_MODEL', ThreadMessage::class),
    ],
];
```

Put options shared by every managed response in `response_options`:

```php
'response_options' => [
    'max_output_tokens' => 1200,
    'reasoning' => ['effort' => 'low'],
],
```

The package always supplies `input` and `conversation`, and it chooses streaming internally. Those keys cannot be replaced through managed thread methods.

The chatbot client is container-isolated from `openai-php/laravel`'s application-level `ClientContract`. Your application's own OpenAI binding and `config/openai.php` can therefore coexist without replacing the credentials or timeout used by this package.

## Quick start

```php
use HalilCosdu\ChatBot\Facades\ChatBot;

$thread = ChatBot::createThread(
    'Explain why the sky is blue.',
    ownerId: auth()->id(),
);

$assistantMessage = ChatBot::updateThread(
    'Why does it turn red at sunset?',
    id: $thread->getKey(),
    ownerId: auth()->id(),
);

echo $assistantMessage->content;
```

The package creates one remote Conversation and stores the user and assistant messages locally. Later calls reuse the remote conversation ID so OpenAI retains the conversation state.

## Managed API

| Method | Result | Purpose |
| --- | --- | --- |
| `createThread($subject, $ownerId, $options)` | Eloquent thread | Create a conversation and wait for the first answer. |
| `createThreadStreamed($subject, $ownerId, $options)` | `StreamedThreadResponse` | Create a conversation and iterate its first answer as text deltas. |
| `updateThread($message, $id, $ownerId, $options)` | Eloquent message | Continue a conversation and wait for the answer. |
| `updateThreadStreamed($message, $id, $ownerId, $options)` | `StreamedThreadResponse` | Continue a conversation and iterate text deltas. |
| `listThreads($ownerId, $search, $appends)` | paginator | List newest threads, optionally scoped and searched. |
| `thread($id, $ownerId)` | Eloquent thread | Retrieve a thread with its messages. |
| `deleteThread($id, $ownerId)` | `void` | Delete the remote Conversation and local thread. |

### Per-request options

The final `$options` argument is merged over `response_options`. It is useful for changing a model, token budget, reasoning effort, metadata, or supported tools for one request:

```php
$message = ChatBot::updateThread(
    message: 'Give me the short version.',
    id: $thread->getKey(),
    ownerId: auth()->id(),
    options: [
        'model' => 'gpt-5.4-mini',
        'max_output_tokens' => 300,
        'reasoning' => ['effort' => 'low'],
        'metadata' => ['feature' => 'support-chat'],
    ],
);
```

Passing a `model` explicitly bypasses a configured `prompt_id`. Passing a `prompt` uses that prompt and omits `model` and `instructions`, matching the package's existing prompt behavior.

## Streaming responses

Streaming uses the Responses API's [semantic events](https://developers.openai.com/api/docs/guides/streaming-responses) internally and exposes only text deltas. The returned object also gives you the local thread immediately:

```php
$stream = ChatBot::createThreadStreamed(
    'Write a short welcome message.',
    ownerId: auth()->id(),
);

$threadId = $stream->thread->getKey();

foreach ($stream as $delta) {
    echo $delta;
}

// Available only after the stream has completed.
$savedMessage = $stream->assistantMessage();
$completed = $stream->completed();
```

`StreamedThreadResponse` is intentionally single-use. A successful terminal `response.completed` event is required before the package stores the assistant message. If the stream fails, ends early, or the consumer disconnects, the user message remains in the local transcript but no incomplete assistant message is invented.

### Laravel streamed HTTP response

```php
use HalilCosdu\ChatBot\Facades\ChatBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/chat', function (Request $request) {
    $stream = ChatBot::createThreadStreamed(
        subject: $request->string('message')->toString(),
        ownerId: $request->user()->getAuthIdentifier(),
    );

    return response()->stream(function () use ($stream): void {
        foreach ($stream as $delta) {
            echo $delta;

            if (ob_get_level() > 0) {
                ob_flush();
            }

            flush();
        }
    }, 200, [
        'Cache-Control' => 'no-cache, no-transform',
        'Content-Type' => 'text/plain; charset=utf-8',
        'X-Accel-Buffering' => 'no',
        'X-Chatbot-Thread' => (string) $stream->thread->getKey(),
    ]);
});
```

Reverse proxies may buffer output even when PHP flushes it. Disable buffering for this endpoint in your web server or proxy configuration. Streaming partial output also makes moderation harder than moderating a completed answer; account for that in production applications.

## Listing and owner isolation

```php
$threads = ChatBot::listThreads(
    ownerId: auth()->id(),
    search: 'invoice',
    appends: request()->query(),
);

$thread = ChatBot::thread($id, ownerId: auth()->id());
ChatBot::deleteThread($id, ownerId: auth()->id());
```

Pass the authenticated owner's ID to every read, update, stream, and delete call. The package applies the owner condition in the database query, including valid IDs such as `0`; a mismatched owner receives Laravel's normal `ModelNotFoundException`.

## Local data and custom models

`threads` stores the owner, short subject, and remote Conversation ID. `thread_messages` stores the ordered local transcript and is deleted with its parent thread.

To add casts, relationships, or tenant behavior, extend the package models:

```php
namespace App\Models;

use HalilCosdu\ChatBot\Models\Thread as BaseThread;

class ChatThread extends BaseThread
{
    protected $table = 'threads';
}
```

Then configure the class:

```dotenv
CHATBOT_THREAD_MODEL="App\Models\ChatThread"
CHATBOT_THREAD_MESSAGE_MODEL="App\Models\ChatThreadMessage"
```

Custom classes must be Eloquent models. Extending the package models is recommended so their relationship methods and bundled factories remain available. The package always uses the stable `thread_id` foreign key, even when your custom class has a different name.

## Raw OpenAI access

Raw methods return the SDK's typed response objects and do not write local models:

```php
ChatBot::createConversationAsRaw(['metadata' => ['tenant' => 'acme']]);
ChatBot::conversationAsRaw($conversationId);
ChatBot::updateConversationAsRaw($conversationId, ['metadata' => ['state' => 'active']]);
ChatBot::listConversationItemsAsRaw($conversationId, ['limit' => 20]);
ChatBot::deleteConversationAsRaw($conversationId);

ChatBot::createResponseAsRaw([
    'model' => 'gpt-5.4-mini',
    'input' => 'Hello',
]);

$events = ChatBot::createResponseStreamedAsRaw([
    'model' => 'gpt-5.4-mini',
    'input' => 'Hello',
]);

foreach ($events as $event) {
    // Inspect $event->event and the typed $event->response payload.
}

ChatBot::responseAsRaw($responseId);
```

Use the managed methods when you want conversation continuity and local persistence. Use the raw methods when you need API features that the managed text workflow does not interpret.

## Migrating from 1.x

Version 1.x used Assistants threads (`thread_*`); version 2.x uses Conversations (`conv_*`). These IDs are not interchangeable.

After publishing and running the 2.x migrations, inspect and migrate your local transcripts:

```bash
php artisan chatbot:migrate-to-conversations --dry-run
php artisan chatbot:migrate-to-conversations
```

Useful options:

```bash
php artisan chatbot:migrate-to-conversations --limit=100
```

The command:

- selects only threads without `remote_conversation_id`;
- reconstructs user messages as `input_text` and assistant messages as `output_text`;
- skips empty transcripts;
- continues after individual API failures and returns a failing exit code if any fail;
- is idempotent, so it can be run again safely.

See [UPGRADE.md](UPGRADE.md) for the complete 1.x to 2.x migration guide.

## Error handling

Managed responses throw `HalilCosdu\ChatBot\Exceptions\ChatBotException` when OpenAI returns a failed or incomplete response, produces no text, or ends a stream without a completion event.

```php
use HalilCosdu\ChatBot\Exceptions\ChatBotException;

try {
    $message = ChatBot::updateThread('Hello', $threadId, auth()->id());
} catch (ChatBotException $exception) {
    report($exception);

    return back()->withErrors(['chat' => 'The assistant could not complete its response.']);
}
```

Transport and authentication errors from the underlying SDK are not hidden. Laravel's `ModelNotFoundException` is also preserved for missing or incorrectly scoped threads.

## Testing and quality

Run the complete local quality gate:

```bash
composer test-all
```

Or run checks independently:

```bash
composer test
composer analyse
composer format-test
composer test-coverage
```

The GitHub compatibility workflow covers PHP 8.2 through 8.5 across Laravel 11, 12, and 13 using each framework's supported PHP range.

## Security

Please do not publish API keys, prompts containing secrets, or sensitive transcript data in issues. Report vulnerabilities privately using the process in [SECURITY.md](SECURITY.md).

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history.

## Credits

- [Halil Cosdu](https://github.com/halilcosdu)
- [All contributors](https://github.com/halilcosdu/laravel-chatbot/graphs/contributors)

## License

Laravel Chatbot is open-sourced software licensed under the [MIT license](LICENSE.md).
