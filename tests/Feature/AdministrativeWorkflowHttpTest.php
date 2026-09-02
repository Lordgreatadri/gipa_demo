<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\EnterpriseType;
use App\Models\Opportunity;
use App\Models\Region;
use App\Models\Sector;
use App\Models\SubSector;
use App\Models\User;
use App\Support\WorkflowPermissions;
use Database\Seeders\GhanaDistrictRegistrySeeder;
use Database\Seeders\WorkflowPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdministrativeWorkflowHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowPermissionSeeder::class);
        Notification::fake();
    }

    public function test_staff_can_submit_and_approve_an_opportunity_through_the_workspace(): void
    {
        [$creator, $reviewer, $opportunity] = $this->context();
        $creator->givePermissionTo(WorkflowPermissions::OPPORTUNITY_SUBMIT);
        $reviewer->givePermissionTo(WorkflowPermissions::OPPORTUNITY_REVIEW);

        $this->actingAs($creator)
            ->post(route('staff.opportunities.transition', [$opportunity, 'submit']), ['reviewer' => $reviewer->uuid])
            ->assertRedirect(route('staff.opportunities.show', $opportunity));

        $this->actingAs($reviewer)
            ->get(route('staff.opportunities.show', $opportunity))
            ->assertOk()
            ->assertSee('Pending Approval')
            ->assertSee('Approve opportunity');

        $this->actingAs($reviewer)
            ->post(route('staff.opportunities.transition', [$opportunity, 'approve']), ['reason' => 'Validated.'])
            ->assertRedirect(route('staff.opportunities.show', $opportunity));

        $this->assertSame(Opportunity::WORKFLOW_APPROVED, $opportunity->fresh()->workflow_status);
        $this->assertDatabaseCount('opportunity_workflow_events', 2);
        $this->assertDatabaseCount('activity_log', 2);
    }

    public function test_investor_cannot_access_the_staff_dashboard(): void
    {
        $investor = User::factory()->create();

        $this->actingAs($investor)->get(route('staff.dashboard'))->assertForbidden();
    }

    public function test_staff_indexes_show_overview_metrics_and_draft_actions(): void
    {
        [$creator, , $opportunity] = $this->context();
        $creator->givePermissionTo([
            WorkflowPermissions::DISTRICT_SUBMIT,
            WorkflowPermissions::OPPORTUNITY_SUBMIT,
        ]);
        $opportunity->district->update([
            'population' => 250000,
            'readiness_score' => 72.5,
            'infrastructure_quality_score' => 68,
        ]);
        $opportunity->financial()->create(['amount' => 1500000, 'currency' => 'GHS']);

        $this->actingAs($creator)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee('Investable portfolio')
            ->assertSee('Opportunity portfolio')
            ->assertSee('Investor onboarding')
            ->assertSee('Application intake')
            ->assertSee('data-nav-group="opportunities"', false)
            ->assertSee('Notifications')
            ->assertSee('data-dashboard-chart', false);

        $this->actingAs($creator)
            ->get(route('staff.districts.overview'))
            ->assertOk()
            ->assertSee('District overview')
            ->assertSee('Investment readiness bands')
            ->assertSee('Regional readiness and reach')
            ->assertSee('250,000')
            ->assertSee('72.5%');

        $this->actingAs($creator)
            ->get(route('staff.districts.index'))
            ->assertOk()
            ->assertSee('District list')
            ->assertSee('Add district')
            ->assertSee(route('staff.districts.edit', $opportunity->district));

        $this->actingAs($creator)
            ->get(route('staff.opportunities.overview'))
            ->assertOk()
            ->assertSee('Opportunity overview')
            ->assertSee('Pipeline value by sector')
            ->assertSee('Expected return by sector')
            ->assertSee('GHS 1.5M');

        $this->actingAs($creator)
            ->get(route('staff.opportunities.index'))
            ->assertOk()
            ->assertSee('Opportunity list')
            ->assertSee('Add opportunity')
            ->assertSee(route('staff.opportunities.create'))
            ->assertSee(route('staff.opportunities.edit', $opportunity));
    }

    public function test_sidebar_destinations_are_distinct_working_pages(): void
    {
        [$staff] = $this->context();
        $staff->givePermissionTo([
            WorkflowPermissions::DISTRICT_SUBMIT,
            WorkflowPermissions::OPPORTUNITY_SUBMIT,
            'investors.view',
        ]);

        $pages = [
            'staff.opportunity-workspace' => 'Portfolio and geographic coverage',
            'staff.regions.index' => 'Region list',
            'staff.districts.overview' => 'District overview',
            'staff.districts.index' => 'District list',
            'staff.opportunities.overview' => 'Opportunity overview',
            'staff.opportunities.index' => 'Opportunity list',
            'staff.reference-data.index' => 'Reference data overview',
            'staff.investments.overview' => 'Investments overview',
            'staff.investors.overview' => 'Investor overview',
            'staff.investors.index' => 'Investor list',
            'staff.inquiries.index' => 'Inquiry list',
            'staff.notifications.overview' => 'Notifications overview',
            'staff.notifications.index' => 'Notification list',
        ];

        foreach ($pages as $route => $heading) {
            $this->actingAs($staff)->get(route($route))->assertOk()->assertSee($heading);
        }

        $this->actingAs($staff)->get(route('staff.opportunity-workspace'))
            ->assertOk()
            ->assertSee('Portfolio and geographic coverage')
            ->assertSee('Active portfolio share')
            ->assertSee('"type":"bar"', false)
            ->assertSee('"type":"pie"', false)
            ->assertSee('Active opportunities');

        foreach (['sectors' => 'Sectors', 'sub-sectors' => 'Sub Sectors', 'enterprise-types' => 'Enterprise Types'] as $section => $heading) {
            $this->actingAs($staff)->get(route('staff.reference-data.section', $section))->assertOk()->assertSee($heading);
        }

        $staff->assignRole('Super Administrator');
        foreach (['staff.users.overview' => 'Users overview', 'staff.users.staff' => 'Staff list', 'staff.users.roles' => 'Roles', 'staff.users.permissions' => 'Permissions'] as $route => $heading) {
            $this->actingAs($staff)->get(route($route))->assertOk()->assertSee($heading);
        }
    }

    public function test_submitter_can_create_update_and_delete_drafts(): void
    {
        [$creator, , $existingOpportunity] = $this->context();
        $creator->givePermissionTo([
            WorkflowPermissions::DISTRICT_SUBMIT,
            WorkflowPermissions::OPPORTUNITY_SUBMIT,
        ]);
        $region = $existingOpportunity->district->region;

        $this->actingAs($creator)->get(route('staff.districts.create'))
            ->assertOk()
            ->assertSee('Create district draft');

        $this->actingAs($creator)->post(route('staff.districts.store'), [
            'region' => $region->uuid,
            'code' => 'TST',
            'name' => 'Test Municipal',
            'population' => 100000,
            'readiness_score' => 60,
        ])->assertRedirect();

        $district = District::query()->where('code', 'TST')->firstOrFail();
        $this->assertSame(District::STATUS_DRAFT, $district->workflow_status);
        $this->actingAs($creator)->get(route('staff.districts.edit', $district))
            ->assertOk()
            ->assertSee('Edit district draft');

        $this->actingAs($creator)->put(route('staff.districts.update', $district), [
            'region' => $region->uuid,
            'code' => 'TST',
            'name' => 'Updated Test Municipal',
            'population' => 125000,
        ])->assertRedirect(route('staff.districts.show', $district));
        $this->assertDatabaseHas('districts', ['id' => $district->id, 'name' => 'Updated Test Municipal']);

        $this->actingAs($creator)->get(route('staff.opportunities.create'))
            ->assertOk()
            ->assertSee('Create opportunity draft');

        $this->actingAs($creator)->post(route('staff.opportunities.store'), [
            'district' => $district->uuid,
            'sector' => $existingOpportunity->sector->uuid,
            'enterprise_type' => $existingOpportunity->enterpriseType->uuid,
            'title' => 'Test investment project',
            'overview' => 'A complete draft investment case.',
        ])->assertRedirect();

        $opportunity = Opportunity::query()->where('title', 'Test investment project')->firstOrFail();
        $this->actingAs($creator)->get(route('staff.opportunities.edit', $opportunity))
            ->assertOk()
            ->assertSee('Edit opportunity draft');
        $this->actingAs($creator)->put(route('staff.opportunities.update', $opportunity), [
            'district' => $district->uuid,
            'sector' => $existingOpportunity->sector->uuid,
            'enterprise_type' => $existingOpportunity->enterpriseType->uuid,
            'title' => 'Updated investment project',
            'overview' => 'The updated draft investment case.',
        ])->assertRedirect(route('staff.opportunities.show', $opportunity));

        $this->actingAs($creator)->delete(route('staff.opportunities.destroy', $opportunity))
            ->assertRedirect(route('staff.opportunities.index'));
        $this->assertSoftDeleted($opportunity);

        $this->actingAs($creator)->delete(route('staff.districts.destroy', $district))
            ->assertRedirect(route('staff.districts.index'));
        $this->assertSoftDeleted($district);
    }

    public function test_records_outside_draft_cannot_be_edited_or_deleted(): void
    {
        [$creator, , $opportunity] = $this->context();
        $creator->givePermissionTo([
            WorkflowPermissions::DISTRICT_SUBMIT,
            WorkflowPermissions::OPPORTUNITY_SUBMIT,
        ]);
        $opportunity->update(['workflow_status' => Opportunity::WORKFLOW_PENDING_APPROVAL]);
        $opportunity->district->update(['workflow_status' => District::STATUS_UNDER_REVIEW]);

        $this->actingAs($creator)->get(route('staff.opportunities.edit', $opportunity))->assertForbidden();
        $this->actingAs($creator)->delete(route('staff.opportunities.destroy', $opportunity))->assertForbidden();
        $this->actingAs($creator)->get(route('staff.districts.edit', $opportunity->district))->assertForbidden();
        $this->actingAs($creator)->delete(route('staff.districts.destroy', $opportunity->district))->assertForbidden();
    }

    public function test_district_form_lists_every_seeded_ghana_region(): void
    {
        $this->seed(GhanaDistrictRegistrySeeder::class);
        $creator = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $creator->givePermissionTo(WorkflowPermissions::DISTRICT_SUBMIT);

        $response = $this->actingAs($creator)->get(route('staff.districts.create'));

        $response->assertOk()->assertViewHas('regions', fn ($regions) => $regions->count() === 16);
        foreach (Region::query()->pluck('name') as $regionName) {
            $response->assertSee($regionName);
        }
    }

    public function test_submitter_can_manage_opportunity_reference_data(): void
    {
        $creator = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $creator->givePermissionTo(WorkflowPermissions::OPPORTUNITY_SUBMIT);

        $this->actingAs($creator)->post(route('staff.reference-data.store', 'sector'), [
            'code' => 'WAT',
            'name' => 'Water infrastructure',
            'description' => 'Water supply and treatment.',
            'is_active' => '1',
        ])->assertRedirect();
        $sector = Sector::query()->where('code', 'WAT')->firstOrFail();

        $this->actingAs($creator)->post(route('staff.reference-data.store', 'sub-sector'), [
            'sector' => $sector->uuid,
            'code' => 'WAT-TREAT',
            'name' => 'Water treatment',
            'is_active' => '1',
        ])->assertRedirect();

        $this->actingAs($creator)->post(route('staff.reference-data.store', 'enterprise-type'), [
            'code' => 'TRUST',
            'name' => 'Public trust',
            'is_active' => '1',
        ])->assertRedirect();

        $type = EnterpriseType::query()->where('code', 'TRUST')->firstOrFail();
        $this->actingAs($creator)->put(route('staff.reference-data.update', ['enterprise-type', $type->uuid]), [
            'code' => 'TRUST',
            'name' => 'Public infrastructure trust',
        ])->assertRedirect();
        $this->assertDatabaseHas('enterprise_types', ['id' => $type->id, 'name' => 'Public infrastructure trust', 'is_active' => false]);

        $this->actingAs($creator)->get(route('staff.reference-data.section', 'sectors'))
            ->assertOk()
            ->assertSee('Water infrastructure');
        $this->actingAs($creator)->get(route('staff.reference-data.section', 'sub-sectors'))
            ->assertOk()
            ->assertSee('Water treatment');
        $this->actingAs($creator)->get(route('staff.reference-data.section', 'enterprise-types'))
            ->assertOk()
            ->assertSee('Public infrastructure trust');

        $this->actingAs($creator)->delete(route('staff.reference-data.destroy', ['enterprise-type', $type->uuid]))
            ->assertRedirect();
        $this->assertDatabaseMissing('enterprise_types', ['id' => $type->id]);
        $this->assertSame(1, SubSector::query()->where('sector_id', $sector->id)->count());
    }

    public function test_staff_without_submit_permission_cannot_manage_reference_data(): void
    {
        $staff = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);

        $this->actingAs($staff)->get(route('staff.reference-data.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('staff.reference-data.store', 'sector'), [
            'code' => 'DENIED',
            'name' => 'Denied sector',
        ])->assertForbidden();
    }

    public function test_staff_indexes_use_bounded_query_counts(): void
    {
        [$creator] = $this->context();
        $creator->givePermissionTo([
            WorkflowPermissions::DISTRICT_SUBMIT,
            WorkflowPermissions::OPPORTUNITY_SUBMIT,
        ]);
        $this->actingAs($creator);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('staff.districts.index'))->assertOk();
        $districtQueryCount = count(DB::getQueryLog());

        DB::flushQueryLog();
        $this->get(route('staff.opportunities.index'))->assertOk();
        $opportunityQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(9, $districtQueryCount);
        $this->assertLessThanOrEqual(9, $opportunityQueryCount);
    }

    private function context(): array
    {
        $creator = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $reviewer = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $region = Region::create(['code' => 'GAR', 'name' => 'Greater Accra']);
        $district = District::create(['region_id' => $region->id, 'code' => 'AMA', 'name' => 'Accra Metropolitan']);
        $sector = Sector::create(['code' => 'MAN', 'name' => 'Manufacturing']);
        $type = EnterpriseType::create(['code' => 'PLL', 'name' => 'Private Limited Liability']);
        $opportunity = Opportunity::create([
            'district_id' => $district->id,
            'sector_id' => $sector->id,
            'enterprise_type_id' => $type->id,
            'title' => 'Industrial services park',
            'overview' => 'A complete investment opportunity ready for review.',
            'created_by' => $creator->id,
        ]);

        return [$creator, $reviewer, $opportunity];
    }
}
