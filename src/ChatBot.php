<?php

namespace HalilCosdu\ChatBot;

use HalilCosdu\ChatBot\Responses\StreamedThreadResponse;
use HalilCosdu\ChatBot\Services\ChatBotService;
use HalilCosdu\ChatBot\Services\OpenAI\RawService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use OpenAI\Responses\Conversations\ConversationDeletedResponse;
use OpenAI\Responses\Conversations\ConversationItemList;
use OpenAI\Responses\Conversations\ConversationResponse;
use OpenAI\Responses\Responses\CreateResponse;
use OpenAI\Responses\Responses\CreateStreamedResponse;
use OpenAI\Responses\Responses\RetrieveResponse;
use OpenAI\Responses\StreamResponse;

readonly class ChatBot
{
    public function __construct(private ChatBotService $chatBotService, private RawService $rawService)
    {
        //
    }

    public function listThreads(mixed $ownerId = null, mixed $search = null, mixed $appends = null): LengthAwarePaginator
    {
        return $this->chatBotService->index($ownerId, $search, $appends);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function createThread(string $subject, mixed $ownerId = null, array $options = []): Model
    {
        return $this->chatBotService->create($subject, $ownerId, $options);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function createThreadStreamed(string $subject, mixed $ownerId = null, array $options = []): StreamedThreadResponse
    {
        return $this->chatBotService->createStreamed($subject, $ownerId, $options);
    }

    public function thread(int $id, mixed $ownerId = null): Model
    {
        return $this->chatBotService->show($id, $ownerId);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function updateThread(string $message, int $id, mixed $ownerId = null, array $options = []): Model
    {
        return $this->chatBotService->update($message, $id, $ownerId, $options);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function updateThreadStreamed(string $message, int $id, mixed $ownerId = null, array $options = []): StreamedThreadResponse
    {
        return $this->chatBotService->updateStreamed($message, $id, $ownerId, $options);
    }

    public function deleteThread(int $id, mixed $ownerId = null): void
    {
        $this->chatBotService->delete($id, $ownerId);
    }

    public function createConversationAsRaw(array $parameters = []): ConversationResponse
    {
        return $this->rawService->createConversationAsRaw($parameters);
    }

    public function conversationAsRaw(string $conversationId): ConversationResponse
    {
        return $this->rawService->conversationAsRaw($conversationId);
    }

    public function updateConversationAsRaw(string $conversationId, array $parameters): ConversationResponse
    {
        return $this->rawService->updateConversationAsRaw($conversationId, $parameters);
    }

    public function deleteConversationAsRaw(string $conversationId): ConversationDeletedResponse
    {
        return $this->rawService->deleteConversationAsRaw($conversationId);
    }

    public function listConversationItemsAsRaw(string $conversationId, array $parameters = []): ConversationItemList
    {
        return $this->rawService->listConversationItemsAsRaw($conversationId, $parameters);
    }

    public function createResponseAsRaw(array $parameters): CreateResponse
    {
        return $this->rawService->createResponseAsRaw($parameters);
    }

    /**
     * @return StreamResponse<CreateStreamedResponse>
     */
    public function createResponseStreamedAsRaw(array $parameters): StreamResponse
    {
        return $this->rawService->createResponseStreamedAsRaw($parameters);
    }

    public function responseAsRaw(string $responseId): RetrieveResponse
    {
        return $this->rawService->responseAsRaw($responseId);
    }
}
