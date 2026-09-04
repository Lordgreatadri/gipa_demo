<?php

namespace Tests\Feature;

use App\Jobs\ReindexAssistantKnowledge;
use App\Models\AssistantConversation;
use App\Services\Assistant\KnowledgeIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AssistantMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_command_deletes_only_stale_conversations(): void
    {
        config()->set('assistant.conversation.retention_days', 30);

        $fresh = AssistantConversation::create([
            'channel' => 'public',
            'session_token' => hash('sha256', 'fresh'),
            'last_activity_at' => now()->subDays(5),
        ]);

        $stale = AssistantConversation::create([
            'channel' => 'public',
            'session_token' => hash('sha256', 'stale'),
            'last_activity_at' => now()->subDays(45),
        ]);

        $this->artisan('assistant:prune-conversations')
            ->assertSuccessful();

        $this->assertModelExists($fresh);
        $this->assertModelMissing($stale);
    }

    public function test_prune_command_honours_days_override(): void
    {
        $recent = AssistantConversation::create([
            'channel' => 'public',
            'session_token' => hash('sha256', 'recent'),
            'last_activity_at' => now()->subDays(10),
        ]);

        $this->artisan('assistant:prune-conversations', ['--days' => 7])
            ->assertSuccessful();

        $this->assertModelMissing($recent);
    }

    public function test_reindex_job_records_completed_status(): void
    {
        (new ReindexAssistantKnowledge(force: true))->handle(app(KnowledgeIndexer::class));

        $status = Cache::get(ReindexAssistantKnowledge::STATUS_CACHE_KEY);

        $this->assertIsArray($status);
        $this->assertSame('completed', $status['state'] ?? null);
    }
}
