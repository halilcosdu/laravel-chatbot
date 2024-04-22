# Laravel AI Chatbot Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/halilcosdu/laravel-chatbot.svg?style=flat-square)](https://packagist.org/packages/halilcosdu/laravel-chatbot)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/halilcosdu/laravel-chatbot/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/halilcosdu/laravel-chatbot/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/halilcosdu/laravel-chatbot/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/halilcosdu/laravel-chatbot/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/halilcosdu/laravel-chatbot.svg?style=flat-square)](https://packagist.org/packages/halilcosdu/laravel-chatbot)

This package, `laravel-chatbot`, provides a robust and easy-to-use solution for integrating AI chatbots into your Laravel applications. Leveraging the power of OpenAI, it allows you to create, manage, and interact with chat threads directly from your Laravel application. Whether you're building a customer service chatbot or an interactive AI assistant, `laravel-chatbot` offers a streamlined, Laravel-friendly interface to the OpenAI API.
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
    'models' => [
        'thread' => \HalilCosdu\ChatBot\Models\Thread::class,
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
ChatBot::listThreads(); /* List all threads */
ChatBot::createThread('Hello'); /* Create a new thread */
ChatBot::thread($id); /* Get a thread with messages */
ChatBot::updateThread('Hi', $id); /* Continue the conversation */
ChatBot::deleteThread($id); /* Delete the thread */
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
