<?php

namespace HalilCosdu\ChatBot\Services\OpenAI;

use OpenAI\Contracts\ClientContract;
use OpenAI\Responses\Conversations\ConversationDeletedResponse;
use OpenAI\Responses\Conversations\ConversationItemList;
use OpenAI\Responses\Conversations\ConversationResponse;
use OpenAI\Responses\Responses\CreateResponse;
use OpenAI\Responses\Responses\RetrieveResponse;

class RawService
{
    public function __construct(public ClientContract $client)
    {
        //
    }

    public function createConversationAsRaw(array $parameters = []): ConversationResponse
    {
        return $this->client->conversations()->create($parameters);
    }

    public function conversationAsRaw(string $conversationId): ConversationResponse
    {
        return $this->client->conversations()->retrieve($conversationId);
    }

    public function updateConversationAsRaw(string $conversationId, array $parameters): ConversationResponse
    {
        return $this->client->conversations()->update($conversationId, $parameters);
    }

    public function deleteConversationAsRaw(string $conversationId): ConversationDeletedResponse
    {
        return $this->client->conversations()->delete($conversationId);
    }

    public function listConversationItemsAsRaw(string $conversationId, array $parameters = []): ConversationItemList
    {
        return $this->client->conversations()->items()->list($conversationId, $parameters);
    }

    public function createResponseAsRaw(array $parameters): CreateResponse
    {
        return $this->client->responses()->create($parameters);
    }

    public function responseAsRaw(string $responseId): RetrieveResponse
    {
        return $this->client->responses()->retrieve($responseId);
    }
}
