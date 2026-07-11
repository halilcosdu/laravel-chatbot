<?php

use HalilCosdu\ChatBot\ChatBot;
use HalilCosdu\ChatBot\Models\Thread;
use HalilCosdu\ChatBot\Responses\StreamedThreadResponse;
use HalilCosdu\ChatBot\Services\ChatBotService;
use HalilCosdu\ChatBot\Services\OpenAI\RawService;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function () {
    $chatBotService = mock(ChatBotService::class);
    $rawService = mock(RawService::class);
    $chatBot = new ChatBot($chatBotService, $rawService);
    $className = config('chatbot.models.thread', Thread::class);
    $mockedThread = new $className;

    $this->chatBotService = $chatBotService;
    $this->rawService = $rawService;
    $this->chatBot = $chatBot;
    $this->mockedThread = $mockedThread;
});

it('lists threads', function () {
    $mockedThreads = new LengthAwarePaginator([], 0, 10);
    $this->chatBotService->shouldReceive('index')->once()->with(null, null, null)->andReturn($mockedThreads);

    $result = $this->chatBot->listThreads();

    expect($result)->toBe($mockedThreads);
});

it('creates a thread', function () {
    $this->chatBotService->shouldReceive('create')->once()->with('subject test', null, [
        'max_output_tokens' => 100,
    ])->andReturn($this->mockedThread);

    $result = $this->chatBot->createThread('subject test', options: [
        'max_output_tokens' => 100,
    ]);

    expect($result)->toBe($this->mockedThread);
});

it('retrieves a thread', function () {
    $this->chatBotService->shouldReceive('show')->once()->with(1, null)->andReturn($this->mockedThread);

    $result = $this->chatBot->thread(1);

    expect($result)->toBe($this->mockedThread);
});

it('updates a thread', function () {
    $this->chatBotService->shouldReceive('update')->once()->with('new message', 1, null, [])->andReturn($this->mockedThread);

    $result = $this->chatBot->updateThread('new message', 1);

    expect($result)->toBe($this->mockedThread);
});

it('deletes a thread', function () {
    $this->chatBotService->shouldReceive('delete')->once()->with(1, null);

    $this->chatBot->deleteThread(1);

    expect(true);
});

it('creates a streamed thread', function () {
    $stream = new StreamedThreadResponse(
        $this->mockedThread,
        function (): Generator {
            if (false) {
                yield '';
            }

            return $this->mockedThread;
        },
    );

    $this->chatBotService->shouldReceive('createStreamed')
        ->once()
        ->with('subject test', 7, [])
        ->andReturn($stream);

    expect($this->chatBot->createThreadStreamed('subject test', 7))->toBe($stream);
});

it('updates a streamed thread', function () {
    $stream = new StreamedThreadResponse(
        $this->mockedThread,
        function (): Generator {
            if (false) {
                yield '';
            }

            return $this->mockedThread;
        },
    );

    $this->chatBotService->shouldReceive('updateStreamed')
        ->once()
        ->with('new message', 1, 7, ['model' => 'gpt-custom'])
        ->andReturn($stream);

    expect($this->chatBot->updateThreadStreamed(
        'new message',
        1,
        7,
        ['model' => 'gpt-custom'],
    ))->toBe($stream);
});
