<?php

namespace HalilCosdu\ChatBot\Commands;

use HalilCosdu\ChatBot\Services\ChatBotService;
use Illuminate\Console\Command;

class MigrateToConversationsCommand extends Command
{
    public $signature = 'chatbot:migrate-to-conversations
                        {--dry-run : List what would be migrated without making API calls}
                        {--limit= : Maximum number of threads to migrate}';

    public $description = 'Migrate legacy Assistants-API threads to Responses/Conversations using the local transcript.';

    public function handle(ChatBotService $service): int
    {
        $client = $service->client;
        $model = config('chatbot.models.thread');
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $query = (new $model)::query()
            ->whereNull('remote_conversation_id')
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $threads = $query->get();

        if ($threads->isEmpty()) {
            $this->info('No threads require migration.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? 'Dry-run: would migrate' : 'Migrating').' '.$threads->count().' thread(s).');

        $migrated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($threads as $thread) {
            $items = $this->buildConversationItems($thread);

            // No transcript to seed a conversation from — skip with a clear reason.
            if ($items === []) {
                $this->warn("Thread [{$thread->id}] has no messages; skipping.");

                $skipped++;

                continue;
            }

            $label = "Thread [{$thread->id}] -> new conversation";

            if ($dryRun) {
                $this->line($label.' (dry-run, '.count($items).' items)');

                continue;
            }

            try {
                $conversation = $client->conversations()->create(['items' => $items]);

                $thread->forceFill(['remote_conversation_id' => $conversation->id])->save();

                $this->info($label." -> {$conversation->id}");

                $migrated++;
            } catch (\Throwable $e) {
                $this->error($label." failed: {$e->getMessage()}");

                $failed++;
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Done. Migrated: %d | Skipped: %d | Failed: %d%s.',
            $migrated,
            $skipped,
            $failed,
            $dryRun ? ' (dry-run)' : ''
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Build Conversations-API items from the local transcript.
     * Per OpenAI's migration guide: user messages use input_text,
     * assistant messages use output_text.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildConversationItems($thread): array
    {
        $items = [];

        foreach ($thread->threadMessages()->orderBy('id')->get() as $message) {
            $contentType = $message->role === 'assistant' ? 'output_text' : 'input_text';

            $items[] = [
                'type' => 'message',
                'role' => $message->role,
                'content' => [
                    ['type' => $contentType, 'text' => $message->content],
                ],
            ];
        }

        return $items;
    }
}
