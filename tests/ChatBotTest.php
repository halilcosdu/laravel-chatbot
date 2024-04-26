<?php

use HalilCosdu\ChatBot\ChatBot;
use HalilCosdu\ChatBot\Services\ChatBotService;
use HalilCosdu\ChatBot\Services\OpenAI\RawService;

beforeEach(function () {
    $chatBotService = Mockery::mock(ChatBotService::class);
    $rawService = Mockery::mock(RawService::class);
    $chatBot = new ChatBot($chatBotService, $rawService);

    $this->chatBotService = $chatBotService;
    $this->rawService = $rawService;
    $this->chatBot = $chatBot;
});

it('lists threads', function () {
    $mockedThreads = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
    $this->chatBotService->shouldReceive('index')->once()->andReturn($mockedThreads);

    $result = $this->chatBot->listThreads();

    expect($result)->toBe($mockedThreads);
});

it('creates a thread', function () {
    $mockedThread = new \HalilCosdu\ChatBot\Models\Thread();
    $this->chatBotService->shouldReceive('create')->once()->andReturn($mockedThread);

    $result = $this->chatBot->createThread('subject test');

    expect($result)->toBe($mockedThread);
});

it('retrieves a thread', function () {
    $mockedThread = new \HalilCosdu\ChatBot\Models\Thread();
    $this->chatBotService->shouldReceive('show')->once()->andReturn($mockedThread);

    $result = $this->chatBot->thread(1);

    expect($result)->toBe($mockedThread);
});

it('updates a thread', function () {
    $mockedThread = new \HalilCosdu\ChatBot\Models\Thread();
    $this->chatBotService->shouldReceive('update')->once()->andReturn($mockedThread);

    $result = $this->chatBot->updateThread('new message', 1);

    expect($result)->toBe($mockedThread);
});

it('deletes a thread', function () {
    $this->chatBotService->shouldReceive('delete')->once();

    $this->chatBot->deleteThread(1);

    expect(true);
});
