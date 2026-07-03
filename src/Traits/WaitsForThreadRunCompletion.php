<?php

namespace HalilCosdu\ChatBot\Traits;

use HalilCosdu\ChatBot\Exceptions\ThreadRunException;
use Illuminate\Support\Sleep;

trait WaitsForThreadRunCompletion
{
    /**
     * Poll a thread run until it reaches a terminal state.
     *
     * Status handling (per the OpenAI Assistants API):
     *  - `completed`: success, returns.
     *  - `failed`, `cancelled`, `expired`, `incomplete`: terminal failures, throw.
     *  - `requires_action`: unsupported here (no tool-output submission), throw.
     *  - `queued`, `in_progress`, `cancelling`: keep polling.
     *
     * @param  int|null  $maxAttempts  Override the configured maximum poll attempts.
     *
     * @throws ThreadRunException when the run fails, needs action, or does not finish in time.
     */
    public function waitForThreadRunCompletion($remoteThreadId, $runId, ?int $maxAttempts = null): void
    {
        $maxAttempts ??= (int) config('chatbot.run_max_attempts', 600);
        $sleepSeconds = (float) config('chatbot.sleep_seconds', 0.1);

        $terminalFailures = ['failed', 'cancelled', 'expired', 'incomplete'];

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            Sleep::sleep($sleepSeconds);

            $run = $this->retrieveRun($remoteThreadId, $runId);

            if ($run->status === 'completed') {
                return;
            }

            if (in_array($run->status, $terminalFailures, true)) {
                throw new ThreadRunException("Thread run [{$runId}] ended with terminal status [{$run->status}].");
            }

            if ($run->status === 'requires_action') {
                throw new ThreadRunException("Thread run [{$runId}] requires action; tool-output submission is not supported by this package.");
            }
        }

        throw new ThreadRunException("Thread run [{$runId}] did not complete within {$maxAttempts} attempts.");
    }

    /**
     * Fetch the current run state. Extracted so tests can override without
     * having to mock the openai-php client chain.
     */
    protected function retrieveRun(string $remoteThreadId, string $runId): object
    {
        return $this->client->threads()->runs()->retrieve($remoteThreadId, $runId);
    }
}
