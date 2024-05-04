<?php

namespace HalilCosdu\ChatBot;

use HalilCosdu\ChatBot\Services\ChatBotService;
use HalilCosdu\ChatBot\Services\OpenAI\RawService;
use InvalidArgumentException;
use OpenAI as OpenAIFactory;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ChatBotServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-chatbot')
            ->hasConfigFile()
            ->hasMigrations(
                [
                    'create_threads_table',
                    'create_thread_messages_table',
                ]
            );
    }

    public function packageRegistered(): void
    {
        $this->registerServices();
    }

    private function registerServices(): void
    {
        $services = [
            ChatBotService::class,
            RawService::class,
        ];

        foreach ($services as $service) {
            $this->app->singleton($service, function () use ($service) {
                $apiKey = config('chatbot.api_key');
                $organization = config('chatbot.organization');

                if (! is_string($apiKey) || ($organization !== null && ! is_string($organization))) {
                    throw new InvalidArgumentException(
                        'The OpenAI API Key is missing. Please publish the [chatbot.php] configuration file and set the [api_key].'
                    );
                }

                $openAI = OpenAIFactory::factory()
                    ->withApiKey($apiKey)
                    ->withOrganization($organization)
                    ->withHttpHeader('OpenAI-Beta', 'assistants=v1')
                    ->withHttpClient(new \GuzzleHttp\Client(['timeout' => config('chatbot.request_timeout', 30)]))
                    ->make();

                return new $service($openAI);
            });
        }
    }
}
