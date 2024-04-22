<?php

namespace HalilCosdu\ChatBot;

use HalilCosdu\ChatBot\Services\ChatBotService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

readonly class ChatBot
{
    public function __construct(private ChatBotService $chatBotService)
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
}
