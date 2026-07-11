<?php

namespace HalilCosdu\ChatBot\Services;

use Generator;
use HalilCosdu\ChatBot\Exceptions\ChatBotException;
use HalilCosdu\ChatBot\Models\Thread;
use HalilCosdu\ChatBot\Models\ThreadMessage;
use HalilCosdu\ChatBot\Responses\StreamedThreadResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use InvalidArgumentException;
use OpenAI\Contracts\ClientContract;
use OpenAI\Responses\Responses\CreateResponse;
use OpenAI\Responses\Responses\Streaming\Error as StreamError;
use OpenAI\Responses\Responses\Streaming\OutputTextDelta;
use OpenAI\Responses\Responses\Streaming\Response as StreamLifecycleEvent;
use Throwable;

class ChatBotService
{
    /** @var class-string<Model> */
    protected string $model;

    /** @var class-string<Model> */
    protected string $messageModel;

    public function __construct(public ClientContract $client)
    {
        $model = config('chatbot.models.thread', Thread::class);
        $messageModel = config('chatbot.models.thread_messages', ThreadMessage::class);

        foreach (['thread' => $model, 'thread_messages' => $messageModel] as $name => $class) {
            if (! is_string($class) || ! is_a($class, Model::class, true)) {
                throw new InvalidArgumentException("The [chatbot.models.{$name}] configuration value must be an Eloquent model class.");
            }
        }

        $this->model = $model;
        $this->messageModel = $messageModel;
    }

    public function index(mixed $ownerId = null, mixed $search = null, mixed $appends = null): LengthAwarePaginator
    {
        return (new $this->model)::query()
            ->when($search !== null && $search !== '', fn ($query) => $query->where('subject', 'like', "%{$search}%"))
            ->when($ownerId !== null, fn ($query) => $query->where('owner_id', $ownerId))
            ->latest()
            ->when($appends !== null, fn ($query) => $query->paginate()->appends($appends), fn ($query) => $query->paginate());
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function create(string $subject, mixed $ownerId = null, array $options = []): Model
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

        try {
            $response = $this->createResponse($conversation->id, [
                ['role' => 'user', 'content' => $subject],
            ], $options);

            $assistantText = $this->extractAssistantText($response);

            $thread->getConnection()->transaction(function () use ($thread, $subject, $assistantText): void {
                $this->messages($thread)->create([
                    'role' => 'user',
                    'content' => $subject,
                ]);

                $this->messages($thread)->create([
                    'role' => 'assistant',
                    'content' => $assistantText,
                ]);
            });
        } catch (Throwable $exception) {
            $thread->delete();

            try {
                $this->client->conversations()->delete($conversation->id);
            } catch (Throwable) {
                // Preserve the original response or persistence exception.
            }

            throw $exception;
        }

        $this->loadMessages($thread);

        return $thread;
    }

    /**
     * Start a new conversation and expose its response as text deltas.
     *
     * @param  array<string, mixed>  $options
     */
    public function createStreamed(string $subject, mixed $ownerId = null, array $options = []): StreamedThreadResponse
    {
        $conversation = $this->client->conversations()->create();

        $thread = (new $this->model)::query()->create([
            'owner_id' => $ownerId,
            'subject' => Str::words($subject, 10),
            'remote_conversation_id' => $conversation->id,
        ]);

        return new StreamedThreadResponse(
            $thread,
            function () use ($thread, $subject, $options): Generator {
                return yield from $this->streamResponse($thread, $subject, $options);
            },
        );
    }

    public function show(int $id, mixed $ownerId = null): Model
    {
        $thread = $this->findThread($id, $ownerId);
        $this->loadMessages($thread);

        return $thread;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function update(string $message, int $id, mixed $ownerId = null, array $options = []): Model
    {
        $thread = $this->findThread($id, $ownerId);
        $conversationId = $this->conversationId($thread, $id);

        $this->messages($thread)->create([
            'role' => 'user',
            'content' => $message,
        ]);

        $response = $this->createResponse($conversationId, [
            ['role' => 'user', 'content' => $message],
        ], $options);

        $assistantMessage = $this->messages($thread)->create([
            'role' => 'assistant',
            'content' => $this->extractAssistantText($response),
        ]);

        $this->loadMessages($thread);

        return $assistantMessage;
    }

    /**
     * Continue a conversation and expose its response as text deltas.
     *
     * @param  array<string, mixed>  $options
     */
    public function updateStreamed(string $message, int $id, mixed $ownerId = null, array $options = []): StreamedThreadResponse
    {
        $thread = $this->findThread($id, $ownerId);
        $this->conversationId($thread, $id);

        return new StreamedThreadResponse(
            $thread,
            function () use ($thread, $message, $options): Generator {
                return yield from $this->streamResponse($thread, $message, $options);
            },
        );
    }

    public function delete(int $id, mixed $ownerId = null): void
    {
        $thread = $this->findThread($id, $ownerId);

        $conversationId = $thread->getAttribute('remote_conversation_id');

        if (is_string($conversationId) && $conversationId !== '') {
            $this->client->conversations()->delete($conversationId);
        }

        $thread->delete();
    }

    /**
     * Create a Responses API call bound to a stored conversation.
     *
     * @param  array<int, array{role: string, content: string}>  $input
     * @param  array<string, mixed>  $options
     */
    protected function createResponse(string $conversationId, array $input, array $options = []): CreateResponse
    {
        return $this->client->responses()->create(
            $this->responseParameters($conversationId, $input, $options)
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return Generator<int, string, mixed, Model>
     */
    protected function streamResponse(Model $thread, string $message, array $options): Generator
    {
        $conversationId = $this->conversationId($thread, (int) $thread->getKey());

        $this->messages($thread)->create([
            'role' => 'user',
            'content' => $message,
        ]);

        $stream = $this->client->responses()->createStreamed(
            $this->responseParameters($conversationId, [
                ['role' => 'user', 'content' => $message],
            ], $options)
        );

        $completedResponse = null;
        $streamedText = '';

        foreach ($stream as $event) {
            if ($event->response instanceof StreamError) {
                throw new ChatBotException("OpenAI response stream failed: {$event->response->message}");
            }

            if ($event->response instanceof OutputTextDelta) {
                if ($event->response->delta !== '') {
                    $streamedText .= $event->response->delta;

                    yield $event->response->delta;
                }

                continue;
            }

            if (! $event->response instanceof StreamLifecycleEvent) {
                continue;
            }

            if (in_array($event->event, ['response.failed', 'response.incomplete'], true)) {
                $this->extractAssistantText($event->response->response);
            }

            if ($event->event === 'response.completed') {
                $completedResponse = $event->response->response;
            }
        }

        if (! $completedResponse instanceof CreateResponse) {
            throw new ChatBotException('OpenAI response stream ended before a completed response event was received.');
        }

        $assistantText = $this->extractAssistantText($completedResponse);

        if ($streamedText === '') {
            yield $assistantText;
        }

        $assistantMessage = $this->messages($thread)->create([
            'role' => 'assistant',
            'content' => $assistantText,
        ]);

        $this->loadMessages($thread);

        return $assistantMessage;
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $input
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function responseParameters(string $conversationId, array $input, array $options = []): array
    {
        $defaults = config('chatbot.response_options', []);

        if (! is_array($defaults)) {
            throw new InvalidArgumentException('The [chatbot.response_options] configuration value must be an array.');
        }

        $parameters = array_filter(
            array_replace($defaults, $options),
            static fn (mixed $value): bool => $value !== null,
        );

        if (! array_key_exists('prompt', $parameters) && ! array_key_exists('model', $parameters)) {
            if ($promptId = config('chatbot.prompt_id')) {
                $parameters['prompt'] = ['id' => $promptId];
            } else {
                $parameters['model'] = config('chatbot.model', 'gpt-5.4-mini');
            }
        }

        if (array_key_exists('prompt', $parameters)) {
            unset($parameters['model'], $parameters['instructions']);
        } elseif (! array_key_exists('instructions', $parameters)) {
            if ($instructions = config('chatbot.instructions')) {
                $parameters['instructions'] = $instructions;
            }
        }

        unset($parameters['stream']);

        $parameters['input'] = $input;
        $parameters['conversation'] = $conversationId;

        return $parameters;
    }

    protected function findThread(int $id, mixed $ownerId = null): Model
    {
        return (new $this->model)::query()
            ->when($ownerId !== null, fn ($query) => $query->where('owner_id', $ownerId))
            ->findOrFail($id);
    }

    protected function conversationId(Model $thread, int $id): string
    {
        $conversationId = $thread->getAttribute('remote_conversation_id');

        if (! is_string($conversationId) || $conversationId === '') {
            throw new ChatBotException(
                "Thread [{$id}] has no remote_conversation_id. Run `php artisan chatbot:migrate-to-conversations` to migrate it from the legacy Assistants API."
            );
        }

        return $conversationId;
    }

    /**
     * Build the message relationship explicitly so custom thread model names
     * still use the package's stable `thread_id` foreign key.
     *
     * @return HasMany<Model, Model>
     */
    protected function messages(Model $thread): HasMany
    {
        return $thread->hasMany($this->messageModel, 'thread_id');
    }

    protected function loadMessages(Model $thread): void
    {
        $thread->setRelation('threadMessages', $this->messages($thread)->get());
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
