<?php

namespace HalilCosdu\ChatBot;

use HalilCosdu\ChatBot\Services\ChatBotService;

readonly class ChatBot
{
    public function __construct(private ChatBotService $chatBotService)
    {
        //
    }

    public function listThreads(mixed $ownerId = null, mixed $search = null, mixed $appends = null): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->chatBotService->index($ownerId, $search, $appends);
    }

    public function createThread(string $subject, mixed $ownerId = null): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder
    {
        return $this->chatBotService->create($subject, $ownerId);
    }

    public function thread(int $id, mixed $ownerId = null): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder
    {
        return $this->chatBotService->show($id, $ownerId);
    }

    public function updateThread(string $message, int $id, mixed $ownerId = null): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder
    {
        return $this->chatBotService->update($message, $id, $ownerId);
    }

    public function deleteThread(int $id, mixed $ownerId = null): void
    {
        $this->chatBotService->delete($id, $ownerId);
    }
}
