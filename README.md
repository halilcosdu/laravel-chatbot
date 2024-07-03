# Laravel AI Chatbot Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/halilcosdu/laravel-chatbot.svg?style=flat-square)](https://packagist.org/packages/halilcosdu/laravel-chatbot)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/halilcosdu/laravel-chatbot/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/halilcosdu/laravel-chatbot/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/halilcosdu/laravel-chatbot/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/halilcosdu/laravel-chatbot/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/halilcosdu/laravel-chatbot.svg?style=flat-square)](https://packagist.org/packages/halilcosdu/laravel-chatbot)

Laravel Chatbot provides a robust and easy-to-use solution for integrating AI chatbots into your Laravel applications. Leveraging the power of OpenAI, it allows you to create, manage, and interact with chat threads directly from your Laravel application. Whether you're building a customer service chatbot or an interactive AI assistant, `laravel-chatbot` offers a streamlined, Laravel-friendly interface to the OpenAI API.
## Installation

You can install the package via composer:

```bash
composer require halilcosdu/laravel-chatbot
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="chatbot-config"
```

This is the contents of the published config file:

You have to create an assistant on OpenAI and get the API key and assistant ID.

https://platform.openai.com/assistants

```php
return [
    'assistant_id' => env('OPENAI_API_ASSISTANT_ID'),
    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),
    'request_timeout' => env('OPENAI_TIMEOUT'),
    'sleep_seconds' => env('OPENAI_SLEEP_SECONDS'), // Sleep seconds between requests default .1
    'models' => [
        // Thread model must have threadMessages(): HasMany relation
        // ThreadMessage model mush have thread(): BelongsTo relation
        'thread' => \HalilCosdu\ChatBot\Models\Thread::class,
        'thread_messages' => \HalilCosdu\ChatBot\Models\ThreadMessage::class
    ],
];
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="chatbot-migrations"
php artisan migrate
```

This will migrate the following tables:
```bash
Schema::create('threads', function (Blueprint $table) {
    $table->id();
    $table->string('owner_id')->nullable()->index();
    $table->string('subject');
    $table->string('remote_thread_id')->index();

    $table->timestamps();
});
```
```bash
Schema::create('thread_messages', function (Blueprint $table) {
    $table->id();
    $table->foreignIdFor(config('chatbot.models.thread'))->constrained()->cascadeOnDelete();
    $table->string('role')->index();
    $table->longText('content');

    $table->timestamps();
});
```

## Usage

```php
public function listThreads(mixed $ownerId = null, mixed $search = null, mixed $appends = null): \Illuminate\Contracts\Pagination\LengthAwarePaginator
public function createThread(string $subject, mixed $ownerId = null): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder
public function thread(int $id, mixed $ownerId = null): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder
public function updateThread(string $message, int $id, mixed $ownerId = null): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder
public function deleteThread(int $id, mixed $ownerId = null): void
```

```php
ChatBot::listThreads(): LengthAwarePaginator; /* List all threads */
ChatBot::createThread('Hello, what is the capital of Turkey?'): Model; /* Create a new thread */
ChatBot::thread($id): Model; /* Get a thread with messages */
ChatBot::updateThread('Where should I visit?', $id): Model; /* Continue the conversation */
ChatBot::deleteThread($id): void; /* Delete the thread */
```

### Raw Data - Not Saved to Database
#### You can use the following methods to interact with the OpenAI API directly.

```php
public function createThreadAsRaw(string $subject)
public function listThreadMessagesAsRaw(string $remoteThreadId)
public function updateThreadAsRaw(string $remoteThreadId, array $data) /* $data = ['role' => 'user or assistant', 'content' => 'Hello'] */
public function deleteThreadAsRaw(string $remoteThreadId)
public function threadAsRaw(string $threadId)
public function messageAsRaw(string $threadId, string $messageId)
public function modifyMessageAsRaw(string $threadId, string $messageId, array $parameters)
```

```php
ChatBot::createThreadAsRaw(string $subject);
ChatBot::listThreadMessagesAsRaw(string $remoteThreadId);
ChatBot::updateThreadAsRaw(string $remoteThreadId, array $data);
ChatBot::deleteThreadAsRaw(string $remoteThreadId);
ChatBot::threadAsRaw(string $threadId);
ChatBot::messageAsRaw(string $threadId, string $messageId);
ChatBot::modifyMessageAsRaw(string $threadId, string $messageId, array $parameters);
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Halil Cosdu](https://github.com/halilcosdu)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
