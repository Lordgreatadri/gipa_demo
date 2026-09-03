<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\EnterpriseType;
use App\Models\InvestorMatchPreference;
use App\Models\InvestorProfile;
use App\Models\Opportunity;
use App\Models\Region;
use App\Models\Sector;
use App\Models\User;
use App\Services\InvestorOpportunityMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestorOpportunityMatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_explainable_published_matches_in_preference_order(): void
    {
        $investor = User::factory()->create(['account_type' => User::ACCOUNT_INVESTOR]);
        $profile = InvestorProfile::create(['user_id' => $investor->id, 'display_name' => $investor->name]);
        $preferredRegion = Region::create(['code' => 'AA', 'name' => 'Greater Accra']);
        $otherRegion = Region::create(['code' => 'AS', 'name' => 'Ashanti']);
        $preferredDistrict = District::create(['region_id' => $preferredRegion->id, 'code' => 'AMA', 'name' => 'Accra Metropolitan', 'workflow_status' => District::STATUS_PUBLISHED, 'published_at' => now(), 'readiness_score' => 90]);
        $otherDistrict = District::create(['region_id' => $otherRegion->id, 'code' => 'KMA', 'name' => 'Kumasi Metropolitan', 'workflow_status' => District::STATUS_PUBLISHED, 'published_at' => now(), 'readiness_score' => 70]);
        $preferredSector = Sector::create(['code' => 'ICT', 'name' => 'Technology']);
        $otherSector = Sector::create(['code' => 'AGR', 'name' => 'Agriculture']);
        $type = EnterpriseType::create(['code' => 'PLL', 'name' => 'Private Limited']);
        $best = $this->opportunity('Preferred technology project', $preferredDistrict, $preferredSector, $type, Opportunity::WORKFLOW_ACTIVE, 500000);
        $this->opportunity('Wrong geography', $otherDistrict, $preferredSector, $type, Opportunity::WORKFLOW_ACTIVE, 500000);
        $this->opportunity('Unpublished project', $preferredDistrict, $preferredSector, $type, Opportunity::WORKFLOW_DRAFT, 500000);
        $this->opportunity('Wrong sector', $preferredDistrict, $otherSector, $type, Opportunity::WORKFLOW_ACTIVE, 500000);
        $preference = InvestorMatchPreference::create(['investor_profile_id' => $profile->id, 'minimum_investment' => 100000, 'maximum_investment' => 1000000, 'currency' => 'GHS', 'minimum_readiness_score' => 60]);
        $preference->sectors()->attach($preferredSector);
        $preference->regions()->attach($preferredRegion);

        $matches = app(InvestorOpportunityMatcher::class)->matches($preference);

        $this->assertCount(1, $matches);
        $this->assertTrue($matches->first()['opportunity']->is($best));
        $this->assertSame(99, $matches->first()['score']);
        $this->assertContains('Preferred sector', $matches->first()['reasons']);
        $this->assertContains('Preferred region', $matches->first()['reasons']);
        $this->assertContains('Investment range', $matches->first()['reasons']);
    }

    private function opportunity(string $title, District $district, Sector $sector, EnterpriseType $type, string $status, int $amount): Opportunity
    {
        $opportunity = Opportunity::create([
            'district_id' => $district->id,
            'sector_id' => $sector->id,
            'enterprise_type_id' => $type->id,
            'title' => $title,
            'overview' => 'A governed investment opportunity.',
            'workflow_status' => $status,
            'published_at' => $status === Opportunity::WORKFLOW_ACTIVE ? now() : null,
        ]);
        $opportunity->financial()->create(['amount' => $amount, 'currency' => 'GHS']);

        return $opportunity;
    }
}
