<?php

namespace App\Jobs;

use App\Services\Assistant\KnowledgeIndexer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Rebuilds the whole assistant knowledge index off the request lifecycle.
 *
 * With the OpenAI driver each chunk requires a network round-trip, so a full
 * re-index can far exceed web/proxy timeouts. Running it as a queued job keeps
 * the admin request fast and records completion/failure status that the
 * knowledge-base screen surfaces to staff.
 */
class ReindexAssistantKnowledge implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const STATUS_CACHE_KEY = 'assistant.reindex.status';

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public readonly bool $force = true) {}

    public function handle(KnowledgeIndexer $indexer): void
    {
        Cache::forever(self::STATUS_CACHE_KEY, [
            'state' => 'running',
            'started_at' => now()->toIso8601String(),
        ]);

        try {
            $chunks = $indexer->reindexAll(force: $this->force);

            Cache::forever(self::STATUS_CACHE_KEY, [
                'state' => 'completed',
                'chunks' => $chunks,
                'finished_at' => now()->toIso8601String(),
            ]);
        } catch (Throwable $e) {
            Cache::forever(self::STATUS_CACHE_KEY, [
                'state' => 'failed',
                'error' => $e->getMessage(),
                'finished_at' => now()->toIso8601String(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Cache::forever(self::STATUS_CACHE_KEY, [
            'state' => 'failed',
            'error' => $exception->getMessage(),
            'finished_at' => now()->toIso8601String(),
        ]);
    }
}
