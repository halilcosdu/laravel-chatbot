<?php

use HalilCosdu\ChatBot\Exceptions\ThreadRunException;
use HalilCosdu\ChatBot\Traits\WaitsForThreadRunCompletion;
use Illuminate\Support\Sleep;

function makeTester(array $statuses): object
{
    return new class($statuses)
    {
        use WaitsForThreadRunCompletion;

        public function __construct(private array $statuses) {}

        protected function retrieveRun(string $remoteThreadId, string $runId): object
        {
            $status = array_shift($this->statuses) ?? 'in_progress';

            return new class($status)
            {
                public function __construct(public string $status) {}
            };
        }
    };
}

beforeEach(function () {
    Sleep::fake();
    config(['chatbot.sleep_seconds' => 0]);
});

it('returns when the run completes', function () {
    makeTester(['completed'])->waitForThreadRunCompletion('thread_1', 'run_1', 5);

    expect(true)->toBeTrue();
});

it('throws when the run reaches a terminal failure status', function ($status) {
    makeTester([$status])->waitForThreadRunCompletion('thread_1', 'run_1', 5);
})->throws(ThreadRunException::class)->with(['failed', 'cancelled', 'expired', 'incomplete']);

it('throws when the run requires action', function () {
    makeTester(['requires_action'])->waitForThreadRunCompletion('thread_1', 'run_1', 5);
})->throws(ThreadRunException::class);

it('throws a timeout exception when the run never finishes', function () {
    makeTester(['in_progress'])->waitForThreadRunCompletion('thread_1', 'run_1', 3);
})->throws(ThreadRunException::class);

it('keeps polling while in_progress and returns once completed', function () {
    makeTester(['in_progress', 'in_progress', 'completed'])->waitForThreadRunCompletion('thread_1', 'run_1', 5);

    expect(true)->toBeTrue();
});
