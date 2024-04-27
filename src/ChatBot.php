<?php

namespace HalilCosdu\ChatBot;

use HalilCosdu\ChatBot\Services\ChatBotService;
use HalilCosdu\ChatBot\Services\OpenAI\RawService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use OpenAI\Responses\Threads\Messages\ThreadMessageListResponse;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use OpenAI\Responses\Threads\ThreadDeleteResponse;
use OpenAI\Responses\Threads\ThreadResponse;

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

    public function createThread(string $subject, mixed $ownerId = null): Model|Builder
    {
        return $this->chatBotService->create($subject, $ownerId);
    }

    public function thread(int $id, mixed $ownerId = null): Model|Builder
    {
        return $this->chatBotService->show($id, $ownerId);
    }

    public function updateThread(string $message, int $id, mixed $ownerId = null): Model|Builder
    {
        return $this->chatBotService->update($message, $id, $ownerId);
    }

    public function deleteThread(int $id, mixed $ownerId = null): void
    {
        $this->chatBotService->delete($id, $ownerId);
    }

    public function createThreadAsRaw(string $subject): ThreadResponse
    {
        return $this->rawService->createThreadAsRaw($subject);
    }

    public function threadAsRaw(string $threadId): ThreadResponse
    {
        return $this->rawService->threadAsRaw($threadId);
    }

    public function messageAsRaw($threadId, $messageId): ThreadMessageResponse
    {
        return $this->rawService->messageAsRaw($threadId, $messageId);
    }

    public function modifyMessageAsRaw(string $threadId, string $messageId, array $parameters): ThreadMessageResponse
    {
        return $this->rawService->modifyMessageAsRaw($threadId, $messageId, $parameters);
    }

    public function listThreadMessagesAsRaw(string $remoteThreadId): ThreadMessageListResponse
    {
        return $this->rawService->listThreadMessagesAsRaw($remoteThreadId);

    }

    public function updateThreadAsRaw(string $remoteThreadId, array $data): ThreadResponse
    {
        return $this->rawService->updateThreadAsRaw($remoteThreadId, $data);
    }

    public function deleteThreadAsRaw(string $remoteThreadId): ThreadDeleteResponse
    {
        return $this->rawService->deleteThreadAsRaw($remoteThreadId);
    }
}
