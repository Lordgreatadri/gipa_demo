<?php

namespace Database\Seeders;

use App\Models\InvestorMatchPreference;
use App\Models\InvestorProfile;
use App\Models\Region;
use App\Models\Sector;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MatchmakingScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $sectorIds = Sector::query()->orderBy('id')->pluck('id')->all();
        $regionIds = Region::query()->whereHas('districts', fn ($query) => $query->whereNotNull('published_at'))
            ->orderBy('id')->pluck('id')->all();
        if ($sectorIds === [] || $regionIds === []) {
            return;
        }

        InvestorProfile::query()
            ->where('status', InvestorProfile::STATUS_ACTIVE)
            ->orderBy('id')
            ->chunkById(100, function ($profiles) use ($sectorIds, $regionIds): void {
                DB::transaction(function () use ($profiles, $sectorIds, $regionIds): void {
                    foreach ($profiles as $profile) {
                        $minimum = 100000 * (1 + ($profile->id % 10));
                        $preference = InvestorMatchPreference::query()->updateOrCreate(
                            ['investor_profile_id' => $profile->id],
                            [
                                'minimum_investment' => $minimum,
                                'maximum_investment' => $minimum * 10,
                                'currency' => 'GHS',
                                'minimum_readiness_score' => 40 + (($profile->id % 5) * 10),
                            ]
                        );
                        $preference->sectors()->sync($this->selection($sectorIds, $profile->id, 2));
                        $preference->regions()->sync($this->selection($regionIds, $profile->id * 3, 2));
                    }
                });
            });
    }

    private function selection(array $ids, int $offset, int $count): array
    {
        $selected = [];
        for ($index = 0; $index < min($count, count($ids)); $index++) {
            $selected[] = $ids[($offset + $index) % count($ids)];
        }

        return array_values(array_unique($selected));
    }
}
