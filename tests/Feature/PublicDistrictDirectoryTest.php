<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDistrictDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_directory_and_api_expose_only_published_districts(): void
    {
        $region = Region::create(['code' => 'GA', 'name' => 'Greater Accra', 'capital' => 'Accra']);
        $published = District::create([
            'region_id' => $region->id,
            'code' => 'AMA',
            'name' => 'Accra Metropolitan',
            'capital' => 'Accra',
            'workflow_status' => District::STATUS_PUBLISHED,
            'published_at' => now(),
            'readiness_score' => 82,
        ]);
        District::create(['region_id' => $region->id, 'code' => 'TMA', 'name' => 'Internal Draft']);

        $this->get('/districts')->assertOk()->assertSee($published->name)->assertDontSee('Internal Draft');
        $this->get("/districts/{$published->uuid}")->assertOk()->assertSee('District profile');
        $this->getJson('/api/v1/districts')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $published->uuid)
            ->assertJsonMissingPath('data.0.boundary');
    }

    public function test_draft_district_detail_is_not_public(): void
    {
        $region = Region::create(['code' => 'AS', 'name' => 'Ashanti']);
        $draft = District::create(['region_id' => $region->id, 'code' => 'KMA', 'name' => 'Kumasi Metropolitan']);

        $this->get("/districts/{$draft->uuid}")->assertNotFound();
    }
}
