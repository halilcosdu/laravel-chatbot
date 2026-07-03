<?php

use HalilCosdu\ChatBot\Services\OpenAI\RawService;
use OpenAI\Responses\Conversations\ConversationDeletedResponse;
use OpenAI\Responses\Conversations\ConversationResponse;
use OpenAI\Responses\Meta\MetaInformation;
use OpenAI\Responses\Responses\CreateResponse;
use OpenAI\Testing\ClientFake;

function rawMeta(): MetaInformation
{
    return MetaInformation::from(['x-request-id' => ['req-1']]);
}

function rawConversation(string $id = 'conv_raw')
{
    return ConversationResponse::from([
        'id' => $id, 'object' => 'conversation', 'created_at' => 1, 'metadata' => [],
    ], rawMeta());
}

function rawDeleted(): ConversationDeletedResponse
{
    return ConversationDeletedResponse::from([
        'id' => 'conv_del', 'object' => 'conversation.deleted', 'deleted' => true,
    ], rawMeta());
}

function rawResponse(string $text = 'out'): CreateResponse
{
    return CreateResponse::from([
        'id' => 'resp_raw', 'object' => 'response', 'created_at' => 1, 'status' => 'completed',
        'model' => 'gpt', 'output' => [['id' => 'm', 'type' => 'message', 'role' => 'assistant', 'status' => 'completed',
            'content' => [['type' => 'output_text', 'text' => $text, 'annotations' => []]]]],
        'output_text' => $text, 'error' => null, 'instructions' => null, 'background' => false,
        'parallel_tool_calls' => false, 'previous_response_id' => null, 'temperature' => 1.0,
        'tool_choice' => 'auto', 'tools' => [], 'top_p' => 1.0, 'max_output_tokens' => null,
        'max_tool_calls' => null, 'prompt' => null, 'user' => null, 'service_tier' => 'auto',
        'metadata' => [], 'incomplete_details' => null, 'reasoning' => null, 'text' => null,
        'truncation' => 'disabled', 'usage' => null, 'store' => true, 'top_logprobs' => null,
    ], rawMeta());
}

it('creates a conversation via raw', function () {
    $service = new RawService(new ClientFake([rawConversation('conv_new')]));

    expect($service->createConversationAsRaw()->id)->toBe('conv_new');
});

it('retrieves a conversation via raw', function () {
    $service = new RawService(new ClientFake([rawConversation('conv_get')]));

    expect($service->conversationAsRaw('conv_get')->id)->toBe('conv_get');
});

it('deletes a conversation via raw', function () {
    $service = new RawService(new ClientFake([rawDeleted()]));

    expect($service->deleteConversationAsRaw('conv_del')->deleted)->toBeTrue();
});

it('creates a response via raw', function () {
    $service = new RawService(new ClientFake([rawResponse('answer')]));

    expect($service->createResponseAsRaw(['input' => []])->outputText)->toBe('answer');
});
