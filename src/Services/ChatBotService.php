<?php

namespace HalilCosdu\ChatBot\Services;

use HalilCosdu\ChatBot\Models\Thread;
use Illuminate\Support\Sleep;
use OpenAI as OpenAIFactory;
use OpenAI\Client;

class ChatBotService
{
    public Client $client;

    public function __construct()
    {
        $this->client = OpenAIFactory::factory()
            ->withApiKey(config('chatbot.api_key'))
            ->withOrganization(config('chatbot.organization'))
            ->withHttpHeader('OpenAI-Beta', 'assistants=v1')
            ->withHttpClient(new \GuzzleHttp\Client(['timeout' => config('chatbot.request_timeout', 30)]))
            ->make();
    }

    public function index(mixed $ownerId = null, mixed $search = null, mixed $appends = null): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Thread::query()
            ->when($search, function ($query) use ($search) {
                $query->where('subject', 'like', "%{$search}%");
            })
            ->when($ownerId, function ($query) use ($ownerId) {
                return $query->where('owner_id', $ownerId);
            }, function ($query) {
                return $query;
            })
            ->latest()
            ->when($appends, function ($query) use ($appends) {
                return $query->paginate()->appends($appends);
            }, function ($query) {
                return $query->paginate();
            });
    }

    public function create(string $subject, mixed $ownerId = null): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder
    {
        $remoteThread = $this->client->threads()->create([
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $subject,
                ],
            ],
        ]);

        $thread = Thread::query()->create([
            'owner_id' => $ownerId,
            'subject' => $subject,
            'remote_thread_id' => $remoteThread->id,
        ]);

        $run = $this->client->threads()->runs()->create($remoteThread->id, [
            'assistant_id' => config('chatbot.assistant_id'),
        ]);

        do {
            Sleep::sleep(0.1);

            $run = $this->client->threads()->runs()->retrieve($remoteThread->id, $run->id);
        } while ($run->status !== 'completed');

        foreach ($this->client->threads()->messages()->list($remoteThread->id)->data as $message) {
            $thread->threadMessages()->create([
                'role' => $message->role,
                'content' => $message->content[0]->text->value,
            ]);
        }

        $thread->load('threadMessages');

        return $thread;
    }

    public function show(int $id, mixed $ownerId = null): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder
    {
        return Thread::query()
            ->with('threadMessages')
            ->when($ownerId, function ($query) use ($ownerId) {
                return $query->where('owner_id', $ownerId);
            }, function ($query) {
                return $query;
            })
            ->findOrFail($id);
    }

    public function update(string $message, int $id, mixed $ownerId = null)
    {
        $thread = Thread::query()
            ->when($ownerId, function ($query) use ($ownerId) {
                return $query->where('owner_id', $ownerId);
            }, function ($query) {
                return $query;
            })
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

        do {
            Sleep::sleep(0.1);

            $run = $this->client->threads()->runs()->retrieve($thread->remote_thread_id, $run->id);
        } while ($run->status !== 'completed');

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
        $thread = Thread::query()
            ->when($ownerId, function ($query) use ($ownerId) {
                return $query->where('owner_id', $ownerId);
            }, function ($query) {
                return $query;
            })
            ->findOrFail($id);

        $this->client->threads()->delete($thread->remote_thread_id);

        $thread->delete();
    }
}
