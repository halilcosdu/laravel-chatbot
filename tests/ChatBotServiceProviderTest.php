<?php

use HalilCosdu\ChatBot\ChatBotServiceProvider;
use HalilCosdu\ChatBot\Services\ChatBotService;
use OpenAI\Contracts\ClientContract;
use OpenAI\Laravel\ServiceProvider as OpenAIServiceProvider;
use OpenAI\Testing\ClientFake;

it('publishes production-safe connection defaults', function () {
    expect(config('chatbot.request_timeout'))->toBe(30)
        ->and(config('chatbot.response_options'))->toBe([])
        ->and(config('chatbot.model'))->toBe('gpt-5.4-mini');
});

it('rejects a missing OpenAI API key when resolving the client', function () {
    config()->set('chatbot.api_key', '');
    $this->app->forgetInstance(ChatBotServiceProvider::CLIENT);

    expect(fn () => $this->app->make(ChatBotServiceProvider::CLIENT))
        ->toThrow(InvalidArgumentException::class, 'API Key is missing');
});

it('rejects invalid OpenAI organization and project values', function (string $key) {
    config()->set("chatbot.{$key}", ['invalid']);
    $this->app->forgetInstance(ChatBotServiceProvider::CLIENT);

    expect(fn () => $this->app->make(ChatBotServiceProvider::CLIENT))
        ->toThrow(InvalidArgumentException::class, "[{$key}]");
})->with(['organization', 'project']);

it('keeps the chatbot client isolated from the application OpenAI client', function () {
    config()->set('openai.api_key', 'application-key');
    config()->set('chatbot.api_key', 'chatbot-key');
    $this->app->register(OpenAIServiceProvider::class);

    $applicationClient = $this->app->make(ClientContract::class);
    $chatbotClient = $this->app->make(ChatBotServiceProvider::CLIENT);

    expect($chatbotClient)->toBeInstanceOf(ClientContract::class)
        ->and($chatbotClient)->not->toBe($applicationClient);
});

it('requires configured package models to be Eloquent models', function (string $key) {
    config()->set("chatbot.models.{$key}", stdClass::class);

    expect(fn () => new ChatBotService(new ClientFake([])))
        ->toThrow(InvalidArgumentException::class, 'Eloquent model');
})->with(['thread', 'thread_messages']);
