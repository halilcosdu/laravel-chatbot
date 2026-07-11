<?php

namespace HalilCosdu\ChatBot\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \HalilCosdu\ChatBot\ChatBot
 *
 * @method static \Illuminate\Contracts\Pagination\LengthAwarePaginator listThreads(mixed $ownerId = null, mixed $search = null, mixed $appends = null)
 * @method static \Illuminate\Database\Eloquent\Model createThread(string $subject, mixed $ownerId = null, array $options = [])
 * @method static \HalilCosdu\ChatBot\Responses\StreamedThreadResponse createThreadStreamed(string $subject, mixed $ownerId = null, array $options = [])
 * @method static \Illuminate\Database\Eloquent\Model thread(int $id, mixed $ownerId = null)
 * @method static \Illuminate\Database\Eloquent\Model updateThread(string $message, int $id, mixed $ownerId = null, array $options = [])
 * @method static \HalilCosdu\ChatBot\Responses\StreamedThreadResponse updateThreadStreamed(string $message, int $id, mixed $ownerId = null, array $options = [])
 * @method static void deleteThread(int $id, mixed $ownerId = null)
 * @method static \OpenAI\Responses\Conversations\ConversationResponse createConversationAsRaw(array $parameters = [])
 * @method static \OpenAI\Responses\Conversations\ConversationResponse conversationAsRaw(string $conversationId)
 * @method static \OpenAI\Responses\Conversations\ConversationResponse updateConversationAsRaw(string $conversationId, array $parameters)
 * @method static \OpenAI\Responses\Conversations\ConversationDeletedResponse deleteConversationAsRaw(string $conversationId)
 * @method static \OpenAI\Responses\Conversations\ConversationItemList listConversationItemsAsRaw(string $conversationId, array $parameters = [])
 * @method static \OpenAI\Responses\Responses\CreateResponse createResponseAsRaw(array $parameters)
 * @method static \OpenAI\Responses\StreamResponse createResponseStreamedAsRaw(array $parameters)
 * @method static \OpenAI\Responses\Responses\RetrieveResponse responseAsRaw(string $responseId)
 */
class ChatBot extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \HalilCosdu\ChatBot\ChatBot::class;
    }
}
