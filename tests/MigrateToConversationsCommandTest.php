<?php

use HalilCosdu\ChatBot\Models\Thread;

beforeEach(function () {
    // dry-run never calls the OpenAI client, so no fake/mock needed here.
});

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
