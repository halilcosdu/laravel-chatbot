<?php

use HalilCosdu\ChatBot\Models\Thread;
use HalilCosdu\ChatBot\Models\ThreadMessage;
use Illuminate\Support\Facades\Schema;

it('provides usable package factories and model relationships', function () {
    $thread = Thread::factory()->create(['owner_id' => 'owner-1']);
    $message = ThreadMessage::factory()->for($thread)->create([
        'role' => 'assistant',
        'content' => 'Hello',
    ]);

    expect($message->thread->is($thread))->toBeTrue()
        ->and($thread->threadMessages()->first()->is($message))->toBeTrue();
});

it('cascades local messages when a thread is deleted', function () {
    $thread = Thread::factory()->create();
    $message = ThreadMessage::factory()->for($thread)->create();

    $thread->delete();

    expect(ThreadMessage::find($message->id))->toBeNull();
});

it('can roll all package migrations down and back up', function () {
    $threads = include __DIR__.'/../database/migrations/create_threads_table.php.stub';
    $messages = include __DIR__.'/../database/migrations/create_thread_messages_table.php.stub';
    $conversationId = include __DIR__.'/../database/migrations/add_remote_conversation_id_to_threads_table.php.stub';

    $messages->down();
    $conversationId->down();
    $threads->down();

    expect(Schema::hasTable('thread_messages'))->toBeFalse()
        ->and(Schema::hasTable('threads'))->toBeFalse();

    $threads->up();
    $messages->up();
    $conversationId->up();

    expect(Schema::hasTable('threads'))->toBeTrue()
        ->and(Schema::hasTable('thread_messages'))->toBeTrue()
        ->and(Schema::hasColumn('threads', 'remote_conversation_id'))->toBeTrue();
});
