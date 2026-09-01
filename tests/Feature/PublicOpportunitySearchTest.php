<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\EnterpriseType;
use App\Models\Opportunity;
use App\Models\Region;
use App\Models\Sector;
use App\Services\PublicOpportunitySearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicOpportunitySearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_only_published_opportunities_in_published_districts(): void
    {
        [$district, $sector, $type] = $this->referenceData();

        $visible = $this->opportunity($district, $sector, $type, [
            'title' => 'Solar processing park',
            'workflow_status' => Opportunity::WORKFLOW_ACTIVE,
            'published_at' => now(),
        ]);
        $this->opportunity($district, $sector, $type, ['title' => 'Internal draft']);

        $unpublishedDistrict = District::create([
            'region_id' => $district->region_id,
            'code' => 'TMA',
            'name' => 'Tema Metropolitan',
        ]);
        $this->opportunity($unpublishedDistrict, $sector, $type, [
            'title' => 'Hidden logistics park',
            'workflow_status' => Opportunity::WORKFLOW_ACTIVE,
            'published_at' => now(),
        ]);

        $results = app(PublicOpportunitySearch::class)->search([]);

        $this->assertSame(1, $results->total());
        $this->assertTrue($results->first()->is($visible));
        $this->assertTrue($results->first()->relationLoaded('financial'));
        $this->assertTrue($results->first()->district->relationLoaded('region'));
    }

    public function test_search_filters_by_cascading_public_identifiers_and_status(): void
    {
        [$district, $sector, $type] = $this->referenceData();
        $matching = $this->opportunity($district, $sector, $type, [
            'title' => 'Regional cassava processing facility',
            'workflow_status' => Opportunity::WORKFLOW_ACTIVE,
            'published_at' => now(),
        ]);
        $this->opportunity($district, $sector, $type, [
            'title' => 'Completed warehouse project',
            'workflow_status' => Opportunity::WORKFLOW_COMPLETED,
            'published_at' => now()->subDay(),
        ]);

        $results = app(PublicOpportunitySearch::class)->search([
            'query' => 'cassava',
            'region' => $district->region->uuid,
            'district' => $district->uuid,
            'sector' => $sector->uuid,
            'type' => $type->uuid,
            'status' => Opportunity::WORKFLOW_ACTIVE,
        ]);

        $this->assertSame(1, $results->total());
        $this->assertTrue($results->first()->is($matching));
    }

    public function test_versioned_api_exposes_only_public_opportunities(): void
    {
        [$district, $sector, $type] = $this->referenceData();
        $visible = $this->opportunity($district, $sector, $type, [
            'workflow_status' => Opportunity::WORKFLOW_ACTIVE,
            'published_at' => now(),
        ]);
        $this->opportunity($district, $sector, $type, ['title' => 'Draft record']);

        $this->getJson('/api/v1/opportunities')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $visible->uuid)
            ->assertJsonPath('meta.per_page', PublicOpportunitySearch::PER_PAGE);
    }

    public function test_public_inquiry_is_validated_and_linked_to_a_visible_opportunity(): void
    {
        [$district, $sector, $type] = $this->referenceData();
        $opportunity = $this->opportunity($district, $sector, $type, [
            'workflow_status' => Opportunity::WORKFLOW_ACTIVE,
            'published_at' => now(),
        ]);

        $this->postJson("/api/v1/opportunities/{$opportunity->uuid}/inquiries", [
            'name' => 'Ama Mensah',
            'email' => 'ama@example.com',
            'message' => 'I would like to receive the investment documentation.',
            'consent' => true,
        ])->assertCreated()->assertJsonStructure(['message', 'reference']);

        $this->assertDatabaseHas('investor_inquiries', [
            'opportunity_id' => $opportunity->id,
            'email' => 'ama@example.com',
            'status' => 'new',
        ]);
    }

    public function test_unpublished_opportunity_detail_is_not_publicly_accessible(): void
    {
        [$district, $sector, $type] = $this->referenceData();
        $draft = $this->opportunity($district, $sector, $type, ['title' => 'Internal draft']);

        $this->get("/opportunities/{$draft->uuid}")->assertNotFound();
    }

    public function test_public_search_and_detail_pages_render_the_discovery_journey(): void
    {
        [$district, $sector, $type] = $this->referenceData();
        $opportunity = $this->opportunity($district, $sector, $type, [
            'title' => 'Accra circular manufacturing campus',
            'workflow_status' => Opportunity::WORKFLOW_ACTIVE,
            'published_at' => now(),
        ]);
        $opportunity->financial()->create(['amount' => 4200000, 'currency' => 'GHS']);

        $this->get('/opportunities?query=circular')
            ->assertOk()
            ->assertSee('Advanced filters')
            ->assertSee($opportunity->title)
            ->assertSee('GHS 4,200,000');

        $this->get("/opportunities/{$opportunity->uuid}")
            ->assertOk()
            ->assertSee($opportunity->title)
            ->assertSee('Financial indicators')
            ->assertSee('Express your interest');
    }

    public function test_search_eager_loads_card_data_with_a_bounded_query_count(): void
    {
        [$district, $sector, $type] = $this->referenceData();
        foreach (range(1, 5) as $number) {
            $this->opportunity($district, $sector, $type, [
                'title' => "Published opportunity {$number}",
                'workflow_status' => Opportunity::WORKFLOW_ACTIVE,
                'published_at' => now(),
            ])->financial()->create(['amount' => $number * 100000]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $results = app(PublicOpportunitySearch::class)->search([]);
        $results->each(fn (Opportunity $opportunity) => [
            $opportunity->district->region->name,
            $opportunity->sector->name,
            $opportunity->enterpriseType->name,
            $opportunity->financial?->amount,
        ]);

        $this->assertLessThanOrEqual(9, count(DB::getQueryLog()));
    }

    private function referenceData(): array
    {
        $region = Region::create(['code' => 'GAR', 'name' => 'Greater Accra']);
        $district = District::create([
            'region_id' => $region->id,
            'code' => 'AMA',
            'name' => 'Accra Metropolitan',
            'workflow_status' => District::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $sector = Sector::create(['code' => 'AGR', 'name' => 'Agriculture']);
        $type = EnterpriseType::create(['code' => 'PRIVATE', 'name' => 'Private Limited Liability']);

        return [$district, $sector, $type];
    }

    private function opportunity(District $district, Sector $sector, EnterpriseType $type, array $attributes): Opportunity
    {
        return Opportunity::create(array_merge([
            'district_id' => $district->id,
            'sector_id' => $sector->id,
            'enterprise_type_id' => $type->id,
            'title' => 'Investment opportunity',
            'overview' => 'A verified investment opportunity.',
        ], $attributes));
    }
}
