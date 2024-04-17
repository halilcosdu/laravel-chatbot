<?php

namespace HalilCosdu\ChatBot;

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
}
