<?php

use HalilCosdu\ChatBot\Exceptions\ChatBotException;
use HalilCosdu\ChatBot\Models\Thread;
use HalilCosdu\ChatBot\Models\ThreadMessage;
use HalilCosdu\ChatBot\Services\ChatBotService;
use OpenAI\Responses\Conversations\ConversationDeletedResponse;
use OpenAI\Responses\Conversations\ConversationResponse;
use OpenAI\Responses\Meta\MetaInformation;
use OpenAI\Responses\Responses\CreateResponse;
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
        $client = new ClientFake([fakeConversation(), fakeResponse('x', status: 'failed')]);
        $service = new ChatBotService($client);

        $service->create('hi');
    })->throws(ChatBotException::class);
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
