<?php

use HalilCosdu\ChatBot\ChatBot;
use HalilCosdu\ChatBot\Models\Thread;
use HalilCosdu\ChatBot\Services\ChatBotService;
use Illuminate\Pagination\LengthAwarePaginator;
use HalilCosdu\ChatBot\Services\OpenAI\RawService;

beforeEach(function () {
    $chatBotService = mock(ChatBotService::class);
    $rawService = mock(RawService::class);
    $chatBot = new ChatBot($chatBotService, $rawService);
    $className = config('chatbot.models.thread', Thread::class);
    $mockedThread = new $className();

    $this->chatBotService = $chatBotService;
    $this->rawService = $rawService;
    $this->chatBot = $chatBot;
    $this->mockedThread = $mockedThread;
});

it('lists threads', function () {
    $mockedThreads = new LengthAwarePaginator([], 0, 10);
    $this->chatBotService->shouldReceive('index')->once()->andReturn($mockedThreads);

    $result = $this->chatBot->listThreads();

    expect($result)->toBe($mockedThreads);
});

it('creates a thread', function () {
    $this->chatBotService->shouldReceive('create')->once()->andReturn($this->mockedThread);

    $result = $this->chatBot->createThread('subject test');

    expect($result)->toBe($this->mockedThread);
});

it('retrieves a thread', function () {
    $this->chatBotService->shouldReceive('show')->once()->andReturn($this->mockedThread);

    $result = $this->chatBot->thread(1);

    expect($result)->toBe($this->mockedThread);
});

it('updates a thread', function () {
    $this->chatBotService->shouldReceive('update')->once()->andReturn($this->mockedThread);

    $result = $this->chatBot->updateThread('new message', 1);

    expect($result)->toBe($this->mockedThread);
});

it('deletes a thread', function () {
    $this->chatBotService->shouldReceive('delete')->once();

    $this->chatBot->deleteThread(1);

    expect(true);
});
