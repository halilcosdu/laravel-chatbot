<?php

namespace HalilCosdu\ChatBot;

use GuzzleHttp\Client;
use HalilCosdu\ChatBot\Commands\MigrateToConversationsCommand;
use InvalidArgumentException;
use OpenAI as OpenAIFactory;
use OpenAI\Contracts\ClientContract;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ChatBotServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-chatbot')
            ->hasConfigFile()
            ->hasCommand(MigrateToConversationsCommand::class)
            ->hasMigrations(
                [
                    'create_threads_table',
                    'create_thread_messages_table',
                    'add_remote_conversation_id_to_threads_table',
                ]
            );
    }

    public function packageRegistered(): void
    {
        $this->registerServices();
    }

    private function registerServices(): void
    {
        $this->app->singleton(ClientContract::class, function () {
            $apiKey = config('chatbot.api_key');
            $organization = config('chatbot.organization');

            if (! is_string($apiKey) || ($organization !== null && ! is_string($organization))) {
                throw new InvalidArgumentException(
                    'The OpenAI API Key is missing. Please publish the [chatbot.php] configuration file and set the [api_key].'
                );
            }

            return OpenAIFactory::factory()
                ->withApiKey($apiKey)
                ->withOrganization($organization)
                ->withHttpClient(new Client(['timeout' => config('chatbot.request_timeout', 30)]))
                ->make();
        });
    }
}
