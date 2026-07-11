<?php

use HalilCosdu\ChatBot\Models\Thread;
use HalilCosdu\ChatBot\Services\ChatBotService;
use OpenAI\Responses\Conversations\ConversationResponse;
use OpenAI\Responses\Meta\MetaInformation;
use OpenAI\Testing\ClientFake;

beforeEach(function () {
    // dry-run never calls the OpenAI client, so no fake/mock needed here.
});

function migrationConversation(string $id = 'conv_migrated'): ConversationResponse
{
    return ConversationResponse::from([
        'id' => $id,
        'object' => 'conversation',
        'created_at' => 1,
        'metadata' => [],
    ], MetaInformation::from(['x-request-id' => ['req-migration']]));
}

it('reports nothing to migrate when there are no threads', function () {
    $this->artisan('chatbot:migrate-to-conversations')
        ->expectsOutputToContain('No threads require migration.')
        ->assertSuccessful();
});

it('dry-runs threads that have a transcript and skips empty ones', function () {
    Thread::factory()->create(); // empty thread (no messages) -> skipped
    $withMessages = Thread::factory()->create();
    $withMessages->threadMessages()->createMany([
        ['role' => 'user', 'content' => 'Hello'],
        ['role' => 'assistant', 'content' => 'Hi there'],
    ]);

    $this->artisan('chatbot:migrate-to-conversations --dry-run')
        ->expectsOutputToContain('Dry-run: would migrate 2 thread(s).')
        ->expectsOutputToContain('has no messages; skipping')
        ->assertSuccessful();
});

it('respects the --limit option', function () {
    Thread::factory()->count(3)->create()->each(function ($thread) {
        $thread->threadMessages()->create(['role' => 'user', 'content' => 'hi']);
    });

    $this->artisan('chatbot:migrate-to-conversations --dry-run --limit=1')
        ->expectsOutputToContain('Dry-run: would migrate 1 thread(s).')
        ->assertSuccessful();
});

it('migrates a transcript and is idempotent', function () {
    $thread = Thread::factory()->create();
    $thread->threadMessages()->createMany([
        ['role' => 'user', 'content' => 'Hello'],
        ['role' => 'assistant', 'content' => 'Hi there'],
    ]);

    $client = new ClientFake([migrationConversation()]);
    $this->app->instance(ChatBotService::class, new ChatBotService($client));

    $this->artisan('chatbot:migrate-to-conversations')
        ->expectsOutputToContain('Migrated: 1 | Skipped: 0 | Failed: 0')
        ->assertSuccessful();

    expect($thread->refresh()->remote_conversation_id)->toBe('conv_migrated');

    $client->conversations()->assertSent(function (string $method, array $parameters): bool {
        return $method === 'create'
            && $parameters['items'][0]['role'] === 'user'
            && $parameters['items'][0]['content'][0]['type'] === 'input_text'
            && $parameters['items'][1]['role'] === 'assistant'
            && $parameters['items'][1]['content'][0]['type'] === 'output_text';
    });

    $this->artisan('chatbot:migrate-to-conversations')
        ->expectsOutputToContain('No threads require migration.')
        ->assertSuccessful();

    $client->conversations()->assertSent(1);
});

it('continues after API failures and returns a failing exit code', function () {
    $thread = Thread::factory()->create();
    $thread->threadMessages()->create(['role' => 'user', 'content' => 'Hello']);

    $this->app->instance(ChatBotService::class, new ChatBotService(
        new ClientFake([new RuntimeException('API unavailable')])
    ));

    $this->artisan('chatbot:migrate-to-conversations')
        ->expectsOutputToContain('failed: API unavailable')
        ->expectsOutputToContain('Failed: 1')
        ->assertFailed();

    expect($thread->refresh()->remote_conversation_id)->toBeNull();
});
