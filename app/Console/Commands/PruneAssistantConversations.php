<?php

namespace App\Console\Commands;

use App\Models\AssistantConversation;
use Illuminate\Console\Command;

/**
 * Deletes assistant conversations (and, by database cascade, their messages)
 * that have been inactive beyond the configured retention window.
 */
class PruneAssistantConversations extends Command
{
    protected $signature = 'assistant:prune-conversations {--days= : Override the configured retention period in days}';

    protected $description = 'Delete assistant conversations older than the configured retention period';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('assistant.conversation.retention_days', 90));

        if ($days <= 0) {
            $this->info('Retention is disabled (retention_days <= 0); nothing pruned.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);

        $deleted = AssistantConversation::query()
            ->where('last_activity_at', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$deleted} assistant conversation(s) inactive since before {$cutoff->toDateString()}.");

        return self::SUCCESS;
    }
}
