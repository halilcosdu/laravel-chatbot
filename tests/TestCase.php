<?php

namespace HalilCosdu\ChatBot\Tests;

use HalilCosdu\ChatBot\ChatBotServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'HalilCosdu\\ChatBot\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            ChatBotServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('chatbot.api_key', 'test-key');
        config()->set('chatbot.model', 'gpt-5.4-mini');

        Schema::enableForeignKeyConstraints();

        $migrations = [
            'create_threads_table',
            'create_thread_messages_table',
            'add_remote_conversation_id_to_threads_table',
        ];

        foreach ($migrations as $migration) {
            $instance = include __DIR__."/../database/migrations/{$migration}.php.stub";
            $instance->up();
        }
    }
}
