<?php

namespace HalilCosdu\ChatBot\Services;

use HalilCosdu\ChatBot\Models\Thread;
use HalilCosdu\ChatBot\Traits\WaitsForThreadRunCompletion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use OpenAI\Client;

class ChatBotService
{
    use WaitsForThreadRunCompletion;

    protected $model = null;

    public function __construct(public Client $client)
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
        $remoteThread = $this->client->threads()->create([
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $subject,
                ],
            ],
        ]);

        $thread = (new $this->model)::query()->create([
            'owner_id' => $ownerId,
            'subject' => Str::words($subject, 10),
            'remote_thread_id' => $remoteThread->id,
        ]);

        $run = $this->client->threads()->runs()->create($remoteThread->id, [
            'assistant_id' => config('chatbot.assistant_id'),
        ]);

        $this->waitForThreadRunCompletion($remoteThread->id, $run->id);

        foreach ($this->client->threads()->messages()->list($remoteThread->id)->data as $message) {
            $thread->threadMessages()->create([
                'role' => $message->role,
                'content' => $message->content[0]->text->value,
            ]);
        }

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

        $thread->threadMessages()->create([
            'role' => 'user',
            'content' => $message,
        ]);

        $this->client->threads()->messages()->create($thread->remote_thread_id, [
            'role' => 'user',
            'content' => $message,
        ]);

        $run = $this->client->threads()->runs()->create($thread->remote_thread_id, [
            'assistant_id' => config('chatbot.assistant_id'),
        ]);

        $this->waitForThreadRunCompletion($thread->remote_thread_id, $run->id);

        $message = $this->client->threads()->messages()->list($thread->remote_thread_id)->data[0];

        $thread->threadMessages()->create([
            'role' => $message->role,
            'content' => $message->content[0]->text->value,
        ]);

        $thread->load('threadMessages');

        $thread = $thread->refresh();

        return $thread->threadMessages->last();
    }

    public function delete(int $id, mixed $ownerId = null): void
    {
        $thread = (new $this->model)::query()
            ->when($ownerId, fn ($query) => $query->where('owner_id', $ownerId))
            ->findOrFail($id);

        $this->client->threads()->delete($thread->remote_thread_id);

        $thread->delete();
    }
}
