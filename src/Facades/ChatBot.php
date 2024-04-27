<?php

namespace HalilCosdu\ChatBot\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \HalilCosdu\ChatBot\ChatBot
 *
 * @method static \HalilCosdu\ChatBot\ChatBot listThreads(mixed $ownerId = null, mixed $search = null, mixed $appends = null)
 * @method static \HalilCosdu\ChatBot\ChatBot createThread(string $subject, mixed $ownerId = null)
 * @method static \HalilCosdu\ChatBot\ChatBot thread(int $id, mixed $ownerId = null)
 * @method static \HalilCosdu\ChatBot\ChatBot updateThread(string $message, int $id, mixed $ownerId = null)
 * @method static \HalilCosdu\ChatBot\ChatBot deleteThread(int $id, mixed $ownerId = null)
 * @method static \HalilCosdu\ChatBot\ChatBot createThreadAsRaw(string $subject)
 * @method static \HalilCosdu\ChatBot\ChatBot listThreadMessagesAsRaw(string $remoteThreadId)
 * @method static \HalilCosdu\ChatBot\ChatBot updateThreadAsRaw(string $remoteThreadId, array $data)
 * @method static \HalilCosdu\ChatBot\ChatBot deleteThreadAsRaw(string $remoteThreadId)
 * @method static \HalilCosdu\ChatBot\ChatBot threadAsRaw(string $threadId)
 * @method static \HalilCosdu\ChatBot\ChatBot messageAsRaw(string $threadId,string $messageId)
 * @method static \HalilCosdu\ChatBot\ChatBot modifyMessageAsRaw(string $threadId, string $messageId, array $parameters)
 */
class ChatBot extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \HalilCosdu\ChatBot\ChatBot::class;
    }
}
