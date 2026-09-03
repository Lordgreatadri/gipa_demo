<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\InvestorMatchPreference;
use App\Models\InvestorProfile;
use App\Models\Region;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\MatchmakingScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchmakingScenarioSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_preferences_for_active_investors_idempotently(): void
    {
        $region = Region::create(['code' => 'GA', 'name' => 'Greater Accra']);
        District::create(['region_id' => $region->id, 'code' => 'AMA', 'name' => 'Accra Metropolitan', 'workflow_status' => District::STATUS_PUBLISHED, 'published_at' => now()]);
        Sector::create(['code' => 'ICT', 'name' => 'Technology']);
        Sector::create(['code' => 'AGR', 'name' => 'Agriculture']);
        foreach (range(1, 3) as $index) {
            $user = User::factory()->create();
            InvestorProfile::create(['user_id' => $user->id, 'display_name' => "Investor {$index}"]);
        }

        $this->seed(MatchmakingScenarioSeeder::class);
        $this->seed(MatchmakingScenarioSeeder::class);

        $this->assertDatabaseCount('investor_match_preferences', 3);
        $this->assertDatabaseCount('investor_match_preference_sector', 6);
        $this->assertDatabaseCount('investor_match_preference_region', 3);
        $this->assertSame(3, InvestorMatchPreference::query()->where('currency', 'GHS')->count());
    }
}
