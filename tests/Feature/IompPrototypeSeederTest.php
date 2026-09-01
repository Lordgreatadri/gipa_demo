<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\EnterpriseType;
use App\Models\InvestmentStructure;
use App\Models\Opportunity;
use App\Models\OpportunityContact;
use App\Models\OpportunityFinancial;
use App\Models\Region;
use App\Models\Sector;
use App\Models\SubSector;
use Database\Seeders\GhanaDistrictRegistrySeeder;
use Database\Seeders\IompPrototypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IompPrototypeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_prototype_seeds_complete_reference_and_opportunity_data_idempotently(): void
    {
        $this->seed(GhanaDistrictRegistrySeeder::class);
        $this->seed(IompPrototypeSeeder::class);
        $this->seed(IompPrototypeSeeder::class);

        $this->assertSame(16, Region::query()->count());
        $this->assertSame(261, District::query()->count());
        $this->assertSame(10, Sector::query()->count());
        $this->assertSame(24, SubSector::query()->count());
        $this->assertSame(6, EnterpriseType::query()->count());
        $this->assertSame(5, InvestmentStructure::query()->count());
        $this->assertSame(156, Opportunity::query()->count());
        $this->assertSame(156, OpportunityFinancial::query()->count());
        $this->assertSame(156, OpportunityContact::query()->count());
        $this->assertGreaterThanOrEqual(150, Opportunity::query()->distinct()->count('district_id'));
    }
}
