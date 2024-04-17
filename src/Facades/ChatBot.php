<?php

namespace HalilCosdu\ChatBot\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \HalilCosdu\ChatBot\ChatBot
 */
class ChatBot extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \HalilCosdu\ChatBot\ChatBot::class;
    }
}
