<?php

namespace HalilCosdu\ChatBot;

use GuzzleHttp\Client;
use HalilCosdu\ChatBot\Commands\MigrateToConversationsCommand;
use HalilCosdu\ChatBot\Services\ChatBotService;
use HalilCosdu\ChatBot\Services\OpenAI\RawService;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;
use OpenAI as OpenAIFactory;
use OpenAI\Contracts\ClientContract;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ChatBotServiceProvider extends PackageServiceProvider
{
    public const CLIENT = 'laravel-chatbot.openai';

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
        $this->app->singleton(self::CLIENT, function (): ClientContract {
            $apiKey = config('chatbot.api_key');
            $organization = config('chatbot.organization');
            $project = config('chatbot.project');

            if (! is_string($apiKey) || trim($apiKey) === '') {
                throw new InvalidArgumentException(
                    'The OpenAI API Key is missing. Please publish the [chatbot.php] configuration file and set the [api_key].'
                );
            }

            foreach (['organization' => $organization, 'project' => $project] as $name => $value) {
                if ($value !== null && ! is_string($value)) {
                    throw new InvalidArgumentException("The OpenAI [{$name}] configuration value must be a string or null.");
                }
            }

            $factory = OpenAIFactory::factory()
                ->withApiKey($apiKey)
                ->withOrganization($organization)
                ->withHttpClient(new Client(['timeout' => (int) config('chatbot.request_timeout', 30)]));

            if ($project !== null && $project !== '') {
                $factory->withProject($project);
            }

            return $factory->make();
        });

        $this->app->singleton(ChatBotService::class, static function (Application $app): ChatBotService {
            return new ChatBotService($app->make(self::CLIENT));
        });

        $this->app->singleton(RawService::class, static function (Application $app): RawService {
            return new RawService($app->make(self::CLIENT));
        });

        $this->app->singleton(ChatBot::class, static function (Application $app): ChatBot {
            return new ChatBot(
                $app->make(ChatBotService::class),
                $app->make(RawService::class),
            );
        });
    }
}
