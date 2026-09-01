<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\EnterpriseType;
use App\Models\Opportunity;
use App\Models\Region;
use App\Models\Sector;
use App\Models\User;
use App\Notifications\WorkflowTransitionNotification;
use App\Services\DistrictWorkflowService;
use App\Services\OpportunityWorkflowService;
use App\Support\WorkflowPermissions;
use Database\Seeders\WorkflowPermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdministrativeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(WorkflowPermissionSeeder::class);
        config()->set('iomp.workflow.district_review_hours', 48);
        config()->set('iomp.workflow.opportunity_review_hours', 72);
        Carbon::setTestNow('2026-08-31 09:00:00');
        Notification::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_opportunity_moves_from_draft_to_active_with_sla_events_audit_and_notifications(): void
    {
        [$creator, $reviewer, $district, $sector, $type] = $this->workflowContext();
        $creator->givePermissionTo(WorkflowPermissions::OPPORTUNITY_SUBMIT, WorkflowPermissions::OPPORTUNITY_LIFECYCLE);
        $reviewer->givePermissionTo(WorkflowPermissions::OPPORTUNITY_REVIEW);
        $opportunity = Opportunity::create([
            'district_id' => $district->id,
            'sector_id' => $sector->id,
            'enterprise_type_id' => $type->id,
            'title' => 'Industrial services park',
            'overview' => 'A complete investment opportunity ready for review.',
            'created_by' => $creator->id,
        ]);

        $service = app(OpportunityWorkflowService::class);
        $submitted = $service->submit($opportunity, $creator, $reviewer);

        $this->assertSame(Opportunity::WORKFLOW_PENDING_APPROVAL, $submitted->workflow_status);
        $this->assertTrue($submitted->reviewer->is($reviewer));
        $this->assertTrue($submitted->sla_due_at->equalTo(now()->addHours(72)));
        $this->assertSame(2, $submitted->version);

        $approved = $service->approve($submitted, $reviewer, 'Validated for activation.');
        $active = $service->activate($approved, $creator);

        $this->assertSame(Opportunity::WORKFLOW_ACTIVE, $active->workflow_status);
        $this->assertNotNull($active->approved_at);
        $this->assertNotNull($active->published_at);
        $this->assertNull($active->sla_due_at);
        $this->assertSame(['submit', 'approve', 'activate'], $active->workflowEvents()->orderBy('id')->pluck('action')->all());
        $this->assertSame(3, Activity::query()->where('subject_type', Opportunity::class)->count());
        Notification::assertSentTo($reviewer, WorkflowTransitionNotification::class);
        Notification::assertSentTo($creator, WorkflowTransitionNotification::class);
    }

    public function test_district_can_be_rejected_resubmitted_and_published(): void
    {
        [$creator, $reviewer, $district] = $this->workflowContext();
        $creator->givePermissionTo(WorkflowPermissions::DISTRICT_SUBMIT);
        $reviewer->givePermissionTo(WorkflowPermissions::DISTRICT_REVIEW);
        $service = app(DistrictWorkflowService::class);

        $submitted = $service->submit($district, $creator, $reviewer);
        $this->assertTrue($submitted->sla_due_at->equalTo(now()->addHours(48)));

        $rejected = $service->reject($submitted, $reviewer, 'Capital information requires confirmation.');
        $this->assertSame(District::STATUS_DRAFT, $rejected->workflow_status);
        $this->assertSame('Capital information requires confirmation.', $rejected->review_reason);

        $resubmitted = $service->submit($rejected, $creator, $reviewer);
        $published = $service->publish($resubmitted, $reviewer, 'Source confirmed.');

        $this->assertSame(District::STATUS_PUBLISHED, $published->workflow_status);
        $this->assertNotNull($published->published_at);
        $this->assertNull($published->sla_due_at);
        $this->assertSame(['submit', 'reject', 'submit', 'publish'], $published->workflowEvents()->orderBy('id')->pluck('action')->all());
    }

    public function test_unauthorized_transition_fails_without_writing_state_or_events(): void
    {
        [$creator, $reviewer, $district, $sector, $type] = $this->workflowContext();
        $opportunity = Opportunity::create([
            'district_id' => $district->id,
            'sector_id' => $sector->id,
            'enterprise_type_id' => $type->id,
            'title' => 'Restricted opportunity',
            'overview' => 'This transition must be denied.',
            'created_by' => $creator->id,
        ]);

        try {
            app(OpportunityWorkflowService::class)->submit($opportunity, $creator, $reviewer);
            $this->fail('The transition should have been denied.');
        } catch (AuthorizationException) {
            $this->assertSame(Opportunity::WORKFLOW_DRAFT, $opportunity->fresh()->workflow_status);
            $this->assertDatabaseCount('opportunity_workflow_events', 0);
            $this->assertDatabaseCount('activity_log', 0);
        }
    }

    public function test_field_agent_can_submit_records_but_cannot_make_workflow_decisions(): void
    {
        $fieldAgent = Role::findByName('Field Agent');

        $this->assertEqualsCanonicalizing([
            WorkflowPermissions::DISTRICT_SUBMIT,
            WorkflowPermissions::OPPORTUNITY_SUBMIT,
        ], $fieldAgent->permissions->pluck('name')->all());
        $this->assertFalse($fieldAgent->hasPermissionTo(WorkflowPermissions::DISTRICT_REVIEW));
        $this->assertFalse($fieldAgent->hasPermissionTo(WorkflowPermissions::DISTRICT_REASSIGN));
        $this->assertFalse($fieldAgent->hasPermissionTo(WorkflowPermissions::OPPORTUNITY_REVIEW));
        $this->assertFalse($fieldAgent->hasPermissionTo(WorkflowPermissions::OPPORTUNITY_REASSIGN));
        $this->assertFalse($fieldAgent->hasPermissionTo(WorkflowPermissions::OPPORTUNITY_LIFECYCLE));
    }

    private function workflowContext(): array
    {
        $creator = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $reviewer = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $region = Region::create(['code' => 'GAR', 'name' => 'Greater Accra']);
        $district = District::create([
            'region_id' => $region->id,
            'code' => 'AMA',
            'name' => 'Accra Metropolitan',
            'created_by' => $creator->id,
        ]);
        $sector = Sector::create(['code' => 'MAN', 'name' => 'Manufacturing']);
        $type = EnterpriseType::create(['code' => 'PLL', 'name' => 'Private Limited Liability']);

        return [$creator, $reviewer, $district, $sector, $type];
    }
}
