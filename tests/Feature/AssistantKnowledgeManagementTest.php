<?php

namespace Tests\Feature;

use App\Models\AssistantDocument;
use App\Models\User;
use App\Support\AssistantPermissions;
use Database\Seeders\WorkflowPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantKnowledgeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowPermissionSeeder::class);
    }

    private function manager(): User
    {
        $user = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $user->givePermissionTo([
            AssistantPermissions::KNOWLEDGE_VIEW,
            AssistantPermissions::KNOWLEDGE_MANAGE,
        ]);

        return $user;
    }

    public function test_staff_without_permission_cannot_view_knowledge_base(): void
    {
        $staff = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);

        $this->actingAs($staff)
            ->get(route('staff.assistant.knowledge.index'))
            ->assertForbidden();
    }

    public function test_manager_can_view_knowledge_base(): void
    {
        $this->actingAs($this->manager())
            ->get(route('staff.assistant.knowledge.index'))
            ->assertOk()
            ->assertSee('Knowledge base');
    }

    public function test_manager_can_create_and_index_a_document(): void
    {
        $this->actingAs($this->manager())
            ->post(route('staff.assistant.knowledge.store'), [
                'title' => 'Investor incentives',
                'category' => 'incentives',
                'summary' => 'Overview of incentives.',
                'body' => 'GIPA facilitates a range of investment incentives for qualifying projects.',
                'is_published' => '1',
            ])
            ->assertRedirect(route('staff.assistant.knowledge.index'));

        $document = AssistantDocument::query()->where('title', 'Investor incentives')->first();

        $this->assertNotNull($document);
        $this->assertSame('investor-incentives', $document->slug);
        $this->assertNotNull($document->indexed_at);
        $this->assertGreaterThanOrEqual(1, $document->chunks()->count());
    }

    public function test_manager_can_update_and_delete_a_document(): void
    {
        $document = AssistantDocument::create([
            'title' => 'Draft doc',
            'slug' => 'draft-doc',
            'category' => 'faq',
            'body' => 'Original content about investor onboarding.',
        ]);

        $this->actingAs($this->manager())
            ->put(route('staff.assistant.knowledge.update', $document), [
                'title' => 'Draft doc',
                'category' => 'faq',
                'body' => 'Updated content about certificate verification and onboarding.',
                'is_published' => '1',
            ])
            ->assertRedirect(route('staff.assistant.knowledge.index'));

        $this->assertStringContainsString('certificate verification', $document->fresh()->body);

        $this->actingAs($this->manager())
            ->delete(route('staff.assistant.knowledge.destroy', $document))
            ->assertRedirect(route('staff.assistant.knowledge.index'));

        $this->assertSoftDeleted('assistant_documents', ['id' => $document->id]);
    }
}
