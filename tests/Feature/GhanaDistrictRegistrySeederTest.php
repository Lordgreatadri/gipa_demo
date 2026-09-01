<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Region;
use Database\Seeders\GhanaDistrictRegistrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GhanaDistrictRegistrySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_seeds_all_regions_districts_and_both_capital_levels_idempotently(): void
    {
        $this->seed(GhanaDistrictRegistrySeeder::class);
        $this->seed(GhanaDistrictRegistrySeeder::class);

        $this->assertSame(16, Region::count());
        $this->assertSame(261, District::count());
        $this->assertSame(16, Region::query()->whereHas('districts')->count());
        $this->assertSame(261, District::query()->whereNotNull('population')->count());
        $this->assertSame(261, District::query()->whereNotNull('readiness_score')->count());
        $this->assertDatabaseHas('regions', ['name' => 'Ashanti', 'capital' => 'Kumasi']);
        $this->assertDatabaseHas('districts', ['name' => 'Adansi North', 'capital' => 'Fomena']);

        $district = District::query()->where('name', 'Adansi North')->firstOrFail();
        $this->assertSame('District', $district->economic_data['category']);
        $this->assertSame(District::STATUS_DRAFT, $district->workflow_status);
    }
}
