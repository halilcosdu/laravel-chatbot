# Laravel AI Chatbot Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/halilcosdu/laravel-chatbot.svg?style=flat-square)](https://packagist.org/packages/halilcosdu/laravel-chatbot)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/halilcosdu/laravel-chatbot/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/halilcosdu/laravel-chatbot/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/halilcosdu/laravel-chatbot/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/halilcosdu/laravel-chatbot/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/halilcosdu/laravel-chatbot.svg?style=flat-square)](https://packagist.org/packages/halilcosdu/laravel-chatbot)

Laravel Chatbot provides a robust and easy-to-use solution for integrating AI chatbots into your Laravel applications. The 2.x line is built on the **OpenAI Responses + Conversations API** (the replacement for the deprecated Assistants API, which shut down on 2026-08-26). It stores a local transcript of every conversation using Eloquent (`Thread` / `ThreadMessage`) and gives you a fluent, Laravel-friendly facade for creating threads, continuing them, and managing them.

> Upgrading from 1.x? See [UPGRADE.md](UPGRADE.md).

## Requirements

- PHP 8.2+
- Laravel 11.29+, 12.12+, or 13.x
- `openai-php/laravel` ^0.20

## Installation

```bash
composer require halilcosdu/laravel-chatbot
```

Publish the config and migrations, then run the migrations:

```bash
php artisan vendor:publish --tag="chatbot-config"
php artisan vendor:publish --tag="chatbot-migrations"
php artisan migrate
```

This is the published config file:

```php
return [
    'model' => env('OPENAI_MODEL', 'gpt-5.4-mini'),
    'instructions' => env('OPENAI_INSTRUCTIONS'),
    'prompt_id' => env('OPENAI_PROMPT_ID'),  // optional; overrides model+instructions
    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),
    'request_timeout' => env('OPENAI_TIMEOUT'),
    'models' => [
        'thread' => env('CHATBOT_THREAD_MODEL', \HalilCosdu\ChatBot\Models\Thread::class),
        'thread_messages' => env('CHATBOT_THREAD_MESSAGE_MODEL', \HalilCosdu\ChatBot\Models\ThreadMessage::class),
    ],
];
```

## Usage

```php
use HalilCosdu\ChatBot\Facades\ChatBot;
```

### Create a thread

```php
$thread = ChatBot::createThread('Why is the sky blue?', ownerId: auth()->id());
// Creates a Conversation, runs a Response against it, and stores the user
// message + assistant reply as ThreadMessage rows.
```

### Continue a thread

```php
$assistantMessage = ChatBot::updateThread('What about at sunset?', $thread->id);
```

### List / show / delete threads

```php
ChatBot::listThreads(ownerId: auth()->id(), search: 'sky');
ChatBot::thread($thread->id);
ChatBot::deleteThread($thread->id);
```

### Raw OpenAI access

```php
ChatBot::createConversationAsRaw();
ChatBot::conversationAsRaw($conversationId);
ChatBot::deleteConversationAsRaw($conversationId);
ChatBot::listConversationItemsAsRaw($conversationId);
ChatBot::createResponseAsRaw(['model' => 'gpt-5.4-mini', 'input' => [...], 'conversation' => $conversationId]);
ChatBot::responseAsRaw($responseId);
```

## Migrating from 1.x (Assistants API)

If you are upgrading an existing 1.x install with data, run the migration command to rebuild each thread's transcript into a new Conversation (the old `thread_*` ids cannot be reused):

```bash
php artisan chatbot:migrate-to-conversations --dry-run
php artisan chatbot:migrate-to-conversations
```

The command is idempotent (skips threads that already have a `remote_conversation_id`), supports `--limit`, and continues on individual failures with a summary.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Halil Cosdu](https://github.com/halilcosdu)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
