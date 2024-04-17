<?php

namespace HalilCosdu\ChatBot\Commands;

use Illuminate\Console\Command;

class ChatBotCommand extends Command
{
    public $signature = 'laravel-chatbot';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
