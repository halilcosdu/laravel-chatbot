<?php

namespace HalilCosdu\ChatBot\Traits;

use Illuminate\Support\Sleep;

trait WaitsForThreadRunCompletion
{
    public function waitForThreadRunCompletion($remoteThreadId, $runId): void
    {
        do {
            Sleep::sleep(config('chatbot.sleep_seconds', .1));

            $run = $this->client->threads()->runs()->retrieve($remoteThreadId, $runId);
        } while ($run->status !== 'completed');
    }
}
