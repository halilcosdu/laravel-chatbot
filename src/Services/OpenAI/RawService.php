<?php

namespace HalilCosdu\ChatBot\Services\OpenAI;

use HalilCosdu\ChatBot\Traits\WaitsForThreadRunCompletion;
use OpenAI\Client;
use OpenAI\Responses\Threads\Messages\ThreadMessageListResponse;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use OpenAI\Responses\Threads\ThreadDeleteResponse;
use OpenAI\Responses\Threads\ThreadResponse;

class RawService
{
    use WaitsForThreadRunCompletion;

    public function __construct(public Client $client)
    {
        //
    }

    public function createThreadAsRaw(string $subject): ThreadResponse
    {
        $remoteThread = $this->client->threads()->create([
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $subject,
                ],
            ],
        ]);

        $run = $this->client->threads()->runs()->create($remoteThread->id, [
            'assistant_id' => config('chatbot.assistant_id'),
        ]);

        $this->waitForThreadRunCompletion($remoteThread->id, $run->id);

        return $this->client->threads()->retrieve($remoteThread->id);
    }

    public function threadAsRaw(string $threadId): ThreadResponse
    {
        return $this->client->threads()->retrieve($threadId);
    }

    public function listThreadMessagesAsRaw(string $remoteThreadId): ThreadMessageListResponse
    {
        return $this->client->threads()->messages()->list($remoteThreadId);
    }

    public function messageAsRaw($threadId, $messageId): ThreadMessageResponse
    {
        return $this->client->threads()->messages()->retrieve($threadId, $messageId);
    }

    public function modifyMessageAsRaw(string $threadId, string $messageId, array $parameters): ThreadMessageResponse
    {
        return $this->client->threads()->messages()->modify($threadId, $messageId, $parameters);
    }

    public function updateThreadAsRaw(string $remoteThreadId, array $data): ThreadResponse
    {
        $this->client->threads()->messages()->create($remoteThreadId, $data);

        $run = $this->client->threads()->runs()->create($remoteThreadId, [
            'assistant_id' => config('chatbot.assistant_id'),
        ]);

        $this->waitForThreadRunCompletion($remoteThreadId, $run->id);

        return $this->client->threads()->retrieve($remoteThreadId);
    }

    public function deleteThreadAsRaw(string $remoteThreadId): ThreadDeleteResponse
    {
        return $this->client->threads()->delete($remoteThreadId);
    }
}
