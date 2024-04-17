<?php

namespace HalilCosdu\ChatBot;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use HalilCosdu\ChatBot\Commands\ChatBotCommand;

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
            ->hasViews()
            ->hasMigration('create_laravel-chatbot_table')
            ->hasCommand(ChatBotCommand::class);
    }
}
