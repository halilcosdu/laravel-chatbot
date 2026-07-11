<?php

use HalilCosdu\ChatBot\Exceptions\ChatBotException;
use HalilCosdu\ChatBot\Models\Thread;
use HalilCosdu\ChatBot\Models\ThreadMessage;
use HalilCosdu\ChatBot\Services\ChatBotService;
use HalilCosdu\ChatBot\Tests\Fixtures\CustomThread;
use HalilCosdu\ChatBot\Tests\Fixtures\CustomThreadMessage;
use OpenAI\Responses\Conversations\ConversationDeletedResponse;
use OpenAI\Responses\Conversations\ConversationResponse;
use OpenAI\Responses\Meta\MetaInformation;
use OpenAI\Responses\Responses\CreateResponse;
use OpenAI\Responses\Responses\CreateStreamedResponse;
use OpenAI\Responses\StreamResponse;
use OpenAI\Testing\ClientFake;

function meta(): MetaInformation
{
    return MetaInformation::from(['x-request-id' => ['req-1']]);
}

function fakeConversation(string $id = 'conv_1'): ConversationResponse
{
    return ConversationResponse::from([
        'id' => $id,
        'object' => 'conversation',
        'created_at' => 1,
        'metadata' => [],
    ], meta());
}

function fakeDeletedConversation(): ConversationDeletedResponse
{
    return ConversationDeletedResponse::from([
        'id' => 'conv_del',
        'object' => 'conversation.deleted',
        'deleted' => true,
    ], meta());
}

function fakeResponse(string $text, string $status = 'completed'): CreateResponse
{
    return CreateResponse::from([
        'id' => 'resp_1',
        'object' => 'response',
        'created_at' => 1,
        'status' => $status,
        'model' => 'gpt-5.4-mini',
        'output' => [
            ['id' => 'msg_1', 'type' => 'message', 'role' => 'assistant', 'status' => 'completed',
                'content' => [['type' => 'output_text', 'text' => $text, 'annotations' => []]]],
        ],
        'output_text' => $text,
        'error' => null,
        'instructions' => null,
        'background' => false,
        'parallel_tool_calls' => false,
        'previous_response_id' => null,
        'temperature' => 1.0,
        'tool_choice' => 'auto',
        'tools' => [],
        'top_p' => 1.0,
        'max_output_tokens' => null,
        'max_tool_calls' => null,
        'prompt' => null,
        'user' => null,
        'service_tier' => 'auto',
        'metadata' => [],
        'incomplete_details' => null,
        'reasoning' => null,
        'text' => null,
        'truncation' => 'disabled',
        'usage' => null,
        'store' => true,
        'top_logprobs' => null,
    ], meta());
}

/**
 * @param  array<int, array<string, mixed>>  $events
 */
function fakeResponseStream(array $events): StreamResponse
{
    $resource = fopen('php://temp', 'r+');

    foreach ($events as $event) {
        fwrite($resource, "event: {$event['type']}\n");
        fwrite($resource, 'data: '.json_encode($event, JSON_THROW_ON_ERROR)."\n\n");
    }

    fwrite($resource, "data: [DONE]\n\n");
    rewind($resource);

    return CreateStreamedResponse::fake($resource);
}

function completedStream(string $text, array $deltas = []): StreamResponse
{
    $events = [];
    $sequence = 1;

    foreach ($deltas as $delta) {
        $events[] = [
            'type' => 'response.output_text.delta',
            'content_index' => 0,
            'delta' => $delta,
            'item_id' => 'msg_stream',
            'output_index' => 0,
            'sequence_number' => $sequence++,
        ];
    }

    $events[] = [
        'type' => 'response.completed',
        'response' => fakeResponse($text)->toArray(),
        'sequence_number' => $sequence,
    ];

    return fakeResponseStream($events);
}

describe('create', function () {
    it('creates a conversation, runs a response, and stores user + assistant messages', function () {
        $client = new ClientFake([fakeConversation('conv_1'), fakeResponse('Hello!')]);
        $service = new ChatBotService($client);

        $thread = $service->create('Why is the sky blue?', ownerId: 7);

        expect($thread->remote_conversation_id)->toBe('conv_1')
            ->and($thread->subject)->toBe('Why is the sky blue?');

        expect($thread->threadMessages)->toHaveCount(2);
        expect(ThreadMessage::where('role', 'user')->first()->content)->toBe('Why is the sky blue?');
        expect(ThreadMessage::where('role', 'assistant')->first()->content)->toBe('Hello!');
    });

    it('throws when the response is not completed', function () {
        $client = new ClientFake([
            fakeConversation(),
            fakeResponse('x', status: 'failed'),
            fakeDeletedConversation(),
        ]);
        $service = new ChatBotService($client);

        try {
            $service->create('hi');
        } finally {
            expect(Thread::query()->count())->toBe(0);
        }
    })->throws(ChatBotException::class);

    it('allows safe per-request response options', function () {
        config()->set('chatbot.prompt_id', 'pmpt_default');
        config()->set('chatbot.response_options', ['max_output_tokens' => 400]);

        $client = new ClientFake([fakeConversation(), fakeResponse('Hello!')]);
        $service = new ChatBotService($client);

        $service->create('Hello', options: [
            'model' => 'gpt-custom',
            'conversation' => 'conv_untrusted',
            'input' => 'untrusted',
            'stream' => true,
        ]);

        $client->responses()->assertSent(function (string $method, array $parameters): bool {
            return $method === 'create'
                && $parameters['model'] === 'gpt-custom'
                && $parameters['conversation'] === 'conv_1'
                && $parameters['input'][0]['content'] === 'Hello'
                && $parameters['max_output_tokens'] === 400
                && ! isset($parameters['prompt'])
                && ! isset($parameters['stream']);
        });
    });
});

describe('update', function () {
    it('continues a migrated thread with a new response', function () {
        $thread = Thread::factory()->create(['remote_conversation_id' => 'conv_existing']);
        $client = new ClientFake([fakeResponse('Reply!')]);
        $service = new ChatBotService($client);

        $assistantMessage = $service->update('follow up?', $thread->id);

        expect($assistantMessage->role)->toBe('assistant')
            ->and($assistantMessage->content)->toBe('Reply!');
        expect(ThreadMessage::where('role', 'user')->latest('id')->first()->content)->toBe('follow up?');
    });

    it('throws a helpful error when the thread has not been migrated', function () {
        $thread = Thread::factory()->create(['remote_conversation_id' => null]);
        $service = new ChatBotService(new ClientFake([]));

        $service->update('hi', $thread->id);
    })->throws(ChatBotException::class);
});

describe('delete', function () {
    it('deletes the remote conversation and the local thread', function () {
        $thread = Thread::factory()->create(['remote_conversation_id' => 'conv_del']);
        $client = new ClientFake([fakeDeletedConversation()]);
        $service = new ChatBotService($client);

        $service->delete($thread->id);

        expect(Thread::find($thread->id))->toBeNull();
    });
});

describe('streaming', function () {
    it('creates a thread, yields text deltas, and stores the completed exchange', function () {
        $client = new ClientFake([
            fakeConversation('conv_stream'),
            completedStream('Hello world', ['Hello', ' ', 'world']),
        ]);
        $service = new ChatBotService($client);

        $response = $service->createStreamed('Say hello', ownerId: 7);

        expect($response->completed())->toBeFalse()
            ->and($response->thread->remote_conversation_id)->toBe('conv_stream');

        $chunks = iterator_to_array($response, false);

        expect($chunks)->toBe(['Hello', ' ', 'world'])
            ->and($response->completed())->toBeTrue()
            ->and($response->assistantMessage()?->getAttribute('content'))->toBe('Hello world');

        expect($response->thread->threadMessages)->toHaveCount(2)
            ->and($response->thread->threadMessages->pluck('role')->all())->toBe(['user', 'assistant']);
    });

    it('continues a thread and falls back to the completed text when no deltas arrive', function () {
        $thread = Thread::factory()->create(['remote_conversation_id' => 'conv_existing']);
        $service = new ChatBotService(new ClientFake([completedStream('Complete text')]));

        $response = $service->updateStreamed('follow up?', $thread->id);

        expect(iterator_to_array($response, false))->toBe(['Complete text'])
            ->and($response->assistantMessage()?->getAttribute('content'))->toBe('Complete text');
    });

    it('never stores an assistant message when the stream fails', function () {
        $thread = Thread::factory()->create(['remote_conversation_id' => 'conv_existing']);
        $failed = fakeResponse('partial', status: 'failed')->toArray();
        $stream = fakeResponseStream([
            [
                'type' => 'response.output_text.delta',
                'content_index' => 0,
                'delta' => 'partial',
                'item_id' => 'msg_stream',
                'output_index' => 0,
                'sequence_number' => 1,
            ],
            [
                'type' => 'response.failed',
                'response' => $failed,
                'sequence_number' => 2,
            ],
        ]);
        $response = (new ChatBotService(new ClientFake([$stream])))
            ->updateStreamed('follow up?', $thread->id);

        expect(fn () => iterator_to_array($response, false))
            ->toThrow(ChatBotException::class, 'did not complete successfully');

        expect($thread->threadMessages()->where('role', 'user')->count())->toBe(1)
            ->and($thread->threadMessages()->where('role', 'assistant')->count())->toBe(0)
            ->and($response->completed())->toBeFalse();
    });

    it('rejects a stream that ends without a completion event', function () {
        $thread = Thread::factory()->create(['remote_conversation_id' => 'conv_existing']);
        $response = (new ChatBotService(new ClientFake([fakeResponseStream([])])))
            ->updateStreamed('follow up?', $thread->id);

        expect(fn () => iterator_to_array($response, false))
            ->toThrow(ChatBotException::class, 'ended before a completed response event');
    });

    it('can only be consumed once', function () {
        $thread = Thread::factory()->create(['remote_conversation_id' => 'conv_existing']);
        $response = (new ChatBotService(new ClientFake([completedStream('Done', ['Done'])])))
            ->updateStreamed('follow up?', $thread->id);

        iterator_to_array($response, false);

        expect(fn () => iterator_to_array($response, false))
            ->toThrow(LogicException::class, 'can only be consumed once');
    });
});

describe('ownership', function () {
    it('scopes owner id zero instead of treating it as missing', function () {
        $owned = Thread::factory()->create(['owner_id' => '0']);
        Thread::factory()->create(['owner_id' => '1']);
        $service = new ChatBotService(new ClientFake([]));

        expect($service->index(ownerId: 0)->total())->toBe(1)
            ->and($service->show($owned->id, ownerId: 0)->is($owned))->toBeTrue();
    });
});

it('supports custom Eloquent model names with the stable thread_id foreign key', function () {
    config()->set('chatbot.models.thread', CustomThread::class);
    config()->set('chatbot.models.thread_messages', CustomThreadMessage::class);

    $service = new ChatBotService(new ClientFake([
        fakeConversation('conv_custom'),
        fakeResponse('Custom reply'),
    ]));

    $thread = $service->create('Custom model test');

    expect($thread)->toBeInstanceOf(CustomThread::class)
        ->and($thread->threadMessages)->toHaveCount(2)
        ->and($thread->threadMessages->first())->toBeInstanceOf(CustomThreadMessage::class)
        ->and($thread->threadMessages->first()->thread_id)->toBe($thread->getKey());
});
