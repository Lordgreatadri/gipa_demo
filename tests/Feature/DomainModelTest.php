<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\EnterpriseType;
use App\Models\InvestmentStructure;
use App\Models\InvestorInquiry;
use App\Models\InvestorOnboardingCase;
use App\Models\InvestorProfile;
use App\Models\Opportunity;
use App\Models\Region;
use App\Models\Sector;
use App\Models\SubSector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class DomainModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_region_and_district_preserve_the_governed_domain_contract(): void
    {
        $region = Region::create([
            'code' => 'GAR',
            'name' => 'Greater Accra',
            'capital' => 'Accra',
        ]);

        $district = District::create([
            'region_id' => $region->id,
            'code' => 'AMA',
            'name' => 'Accra Metropolitan',
            'readiness_score' => 82.5,
            'economic_data' => ['dominant_sector' => 'Services'],
        ]);

        $this->assertNotNull($region->uuid);
        $this->assertNotNull($district->uuid);
        $this->assertTrue($district->region->is($region));
        $this->assertTrue($region->districts->contains($district));
        $this->assertSame(District::STATUS_DRAFT, $district->workflow_status);
        $this->assertSame('82.50', $district->readiness_score);
        $this->assertSame(['dominant_sector' => 'Services'], $district->economic_data);
        $this->assertSame('uuid', $district->getRouteKeyName());
    }

    public function test_reference_models_expose_filter_relationships_and_defaults(): void
    {
        $sector = Sector::create([
            'code' => 'AGR',
            'name' => 'Agriculture',
        ]);
        $subSector = SubSector::create([
            'sector_id' => $sector->id,
            'code' => 'AGR-PRO',
            'name' => 'Agro-processing',
        ]);
        $enterpriseType = EnterpriseType::create([
            'code' => 'PPP',
            'name' => 'Public Private Partnership',
        ]);
        $investmentStructure = InvestmentStructure::create([
            'code' => 'EQUITY',
            'name' => 'Equity',
        ]);

        $this->assertTrue($subSector->sector->is($sector));
        $this->assertTrue($sector->subSectors->contains($subSector));
        $this->assertTrue($sector->is_active);
        $this->assertTrue($enterpriseType->is_active);
        $this->assertTrue($investmentStructure->is_active);
        $this->assertSame('uuid', $sector->getRouteKeyName());
    }

    public function test_opportunity_aggregate_preserves_the_prototype_data_model(): void
    {
        $user = User::factory()->create();
        $region = Region::create(['code' => 'ASH', 'name' => 'Ashanti']);
        $district = District::create([
            'region_id' => $region->id,
            'code' => 'KMA',
            'name' => 'Kumasi Metropolitan',
        ]);
        $sector = Sector::create(['code' => 'MAN', 'name' => 'Manufacturing']);
        $subSector = SubSector::create([
            'sector_id' => $sector->id,
            'code' => 'MAN-TEX',
            'name' => 'Textiles',
        ]);
        $enterpriseType = EnterpriseType::create(['code' => 'PRIVATE', 'name' => 'Private']);
        $structure = InvestmentStructure::create(['code' => 'DEBT', 'name' => 'Debt']);

        $opportunity = Opportunity::create([
            'district_id' => $district->id,
            'sector_id' => $sector->id,
            'sub_sector_id' => $subSector->id,
            'enterprise_type_id' => $enterpriseType->id,
            'title' => 'Textile Manufacturing Hub',
            'overview' => 'An export-focused textile manufacturing opportunity.',
            'created_by' => $user->id,
        ]);
        $financial = $opportunity->financial()->create([
            'investment_structure_id' => $structure->id,
            'amount' => 2500000,
            'roi_percentage' => 14.25,
        ]);
        $contact = $opportunity->contacts()->create([
            'name' => 'Investment Desk',
            'email' => 'investment@example.com',
            'is_primary' => true,
        ]);
        $event = $opportunity->workflowEvents()->create([
            'actor_id' => $user->id,
            'action' => 'submit',
            'from_status' => Opportunity::WORKFLOW_DRAFT,
            'to_status' => Opportunity::WORKFLOW_PENDING_APPROVAL,
        ]);
        $inquiry = $opportunity->inquiries()->create([
            'reference' => 'INQ-2026-0001',
            'investor_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'message' => 'Please provide the investment memorandum.',
        ]);

        $this->assertSame(Opportunity::WORKFLOW_DRAFT, $opportunity->workflow_status);
        $this->assertSame(Opportunity::PROJECT_STATUS_PROPOSED, $opportunity->project_status);
        $this->assertTrue($opportunity->district->is($district));
        $this->assertTrue($opportunity->sector->is($sector));
        $this->assertTrue($opportunity->subSector->is($subSector));
        $this->assertTrue($opportunity->enterpriseType->is($enterpriseType));
        $this->assertTrue($opportunity->creator->is($user));
        $this->assertTrue($opportunity->financial->is($financial));
        $this->assertTrue($opportunity->contacts->contains($contact));
        $this->assertTrue($opportunity->workflowEvents->contains($event));
        $this->assertTrue($opportunity->inquiries->contains($inquiry));
        $this->assertSame('GHS', $financial->currency);
        $this->assertSame(InvestorInquiry::STATUS_NEW, $inquiry->status);
        $this->assertTrue($user->isActive());
        $this->assertEqualsCanonicalizing([
            Opportunity::DOCUMENT_BUSINESS_PLAN,
            Opportunity::DOCUMENT_TECHNICAL_FEASIBILITY,
            Opportunity::DOCUMENT_MARKET_FEASIBILITY,
            Opportunity::DOCUMENT_FUNDING_STRUCTURE,
        ], $opportunity->getRegisteredMediaCollections()->pluck('name')->all());
    }

    public function test_workflow_events_are_immutable(): void
    {
        $region = Region::create(['code' => 'WR', 'name' => 'Western']);
        $district = District::create([
            'region_id' => $region->id,
            'code' => 'STMA',
            'name' => 'Sekondi-Takoradi Metropolitan',
        ]);
        $event = $district->workflowEvents()->create([
            'action' => 'submit',
            'from_status' => District::STATUS_DRAFT,
            'to_status' => District::STATUS_UNDER_REVIEW,
        ]);

        $this->expectException(LogicException::class);

        $event->update(['reason' => 'Attempted revision']);
    }

    public function test_investor_profile_owns_an_immutable_onboarding_history(): void
    {
        $user = User::factory()->create();
        $profile = $user->investorProfile()->create([
            'display_name' => $user->name,
            'created_by' => $user->id,
        ]);
        $case = $profile->onboardingCases()->create([
            'reference' => 'ONB-2026-000001',
            'created_by' => $user->id,
        ]);
        $event = $case->events()->create([
            'actor_id' => $user->id,
            'action' => 'created',
            'to_status' => InvestorOnboardingCase::STATUS_DRAFT,
            'metadata' => ['schema_version' => 1],
        ]);

        $this->assertTrue($profile->user->is($user));
        $this->assertTrue($user->investorProfile->is($profile));
        $this->assertTrue($case->profile->is($profile));
        $this->assertTrue($event->onboardingCase->is($case));
        $this->assertSame(InvestorProfile::ONBOARDING_NOT_STARTED, $profile->onboarding_state);
        $this->assertSame(InvestorOnboardingCase::STATUS_DRAFT, $case->status);
        $this->assertSame(['schema_version' => 1], $event->metadata);
        $this->assertNotNull($profile->uuid);
        $this->assertNotNull($case->uuid);
        $this->assertSame('uuid', $profile->getRouteKeyName());

        $this->expectException(LogicException::class);
        $event->delete();
    }
}