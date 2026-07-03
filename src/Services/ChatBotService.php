<?php

namespace HalilCosdu\ChatBot\Services;

use HalilCosdu\ChatBot\Exceptions\ChatBotException;
use HalilCosdu\ChatBot\Models\Thread;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use OpenAI\Contracts\ClientContract;
use OpenAI\Responses\Responses\CreateResponse;

class ChatBotService
{
    protected string $model;

    public function __construct(public ClientContract $client)
    {
        $this->model = config('chatbot.models.thread', Thread::class);
    }

    public function index(mixed $ownerId = null, mixed $search = null, mixed $appends = null): LengthAwarePaginator
    {
        return (new $this->model)::query()
            ->when($search, fn ($query) => $query->where('subject', 'like', "%{$search}%"))
            ->when($ownerId, fn ($query) => $query->where('owner_id', $ownerId))
            ->latest()
            ->when($appends, fn ($query) => $query->paginate()->appends($appends), fn ($query) => $query->paginate());
    }

    public function create(string $subject, mixed $ownerId = null): Model|Builder
    {
        // Create an empty conversation. The user input is sent through the
        // Responses API below, which appends both the user input and the
        // assistant output to the conversation automatically — so we must NOT
        // also add the user item here (that would duplicate context).
        $conversation = $this->client->conversations()->create();

        $thread = (new $this->model)::query()->create([
            'owner_id' => $ownerId,
            'subject' => Str::words($subject, 10),
            'remote_conversation_id' => $conversation->id,
        ]);

        $response = $this->createResponse($conversation->id, [
            ['role' => 'user', 'content' => $subject],
        ]);

        $thread->threadMessages()->create([
            'role' => 'user',
            'content' => $subject,
        ]);

        $thread->threadMessages()->create([
            'role' => 'assistant',
            'content' => $this->extractAssistantText($response),
        ]);

        $thread->load('threadMessages');

        return $thread;
    }

    public function show(int $id, mixed $ownerId = null): Model|Builder
    {
        return (new $this->model)::query()
            ->with('threadMessages')
            ->when($ownerId, fn ($query) => $query->where('owner_id', $ownerId))
            ->findOrFail($id);
    }

    public function update(string $message, int $id, mixed $ownerId = null)
    {
        $thread = (new $this->model)::query()
            ->when($ownerId, fn ($query) => $query->where('owner_id', $ownerId))
            ->findOrFail($id);

        if (empty($thread->remote_conversation_id)) {
            throw new ChatBotException(
                "Thread [{$id}] has no remote_conversation_id. Run `php artisan chatbot:migrate-to-conversations` to migrate it from the legacy Assistants API."
            );
        }

        $thread->threadMessages()->create([
            'role' => 'user',
            'content' => $message,
        ]);

        $response = $this->createResponse($thread->remote_conversation_id, [
            ['role' => 'user', 'content' => $message],
        ]);

        $assistantMessage = $thread->threadMessages()->create([
            'role' => 'assistant',
            'content' => $this->extractAssistantText($response),
        ]);

        $thread->load('threadMessages');

        return $assistantMessage;
    }

    public function delete(int $id, mixed $ownerId = null): void
    {
        $thread = (new $this->model)::query()
            ->when($ownerId, fn ($query) => $query->where('owner_id', $ownerId))
            ->findOrFail($id);

        if (! empty($thread->remote_conversation_id)) {
            $this->client->conversations()->delete($thread->remote_conversation_id);
        }

        $thread->delete();
    }

    /**
     * Create a Responses API call bound to a stored conversation.
     *
     * @param  array<int, array{role: string, content: string}>  $input
     */
    protected function createResponse(string $conversationId, array $input): CreateResponse
    {
        $parameters = [
            'input' => $input,
            'conversation' => $conversationId,
        ];

        if ($promptId = config('chatbot.prompt_id')) {
            $parameters['prompt'] = ['id' => $promptId];
        } else {
            $parameters['model'] = config('chatbot.model', 'gpt-5.4-mini');
            if ($instructions = config('chatbot.instructions')) {
                $parameters['instructions'] = $instructions;
            }
        }

        return $this->client->responses()->create($parameters);
    }

    /**
     * Pull the assistant's text output from a completed response.
     */
    protected function extractAssistantText(CreateResponse $response): string
    {
        if ($response->status !== 'completed') {
            $error = $response->error !== null
                ? $response->error->message
                : "Response ended with status [{$response->status}].";

            throw new ChatBotException("OpenAI response did not complete successfully: {$error}");
        }

        $text = $response->outputText;

        if ($text === null || trim($text) === '') {
            throw new ChatBotException('OpenAI response completed without any text output.');
        }

        return $text;
    }
}
