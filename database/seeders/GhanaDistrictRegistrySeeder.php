<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GhanaDistrictRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $registry = json_decode(
            file_get_contents(database_path('data/ghana-districts.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        DB::transaction(function () use ($registry): void {
            $districtIndex = 0;

            foreach ($registry['regions'] as $regionData) {
                $region = Region::query()->updateOrCreate(
                    ['name' => $regionData['name']],
                    ['code' => $regionData['code'], 'capital' => $regionData['capital']],
                );

                foreach ($regionData['districts'] as $districtData) {
                    $districtIndex++;
                    District::query()->updateOrCreate(
                        ['region_id' => $region->id, 'name' => $districtData['name']],
                        [
                            'code' => $districtData['code'],
                            'capital' => $districtData['capital'],
                            'population' => 45000 + (($districtIndex * 17329) % 420000),
                            'area_sq_km' => 85 + (($districtIndex * 37) % 1850),
                            'readiness_score' => 48 + (($districtIndex * 7) % 47),
                            'infrastructure_quality_score' => 42 + (($districtIndex * 11) % 53),
                            'economic_data' => [
                                'category' => $districtData['category'],
                                'established' => $districtData['established'],
                                'source' => $registry['meta']['source'],
                            ],
                        ],
                    );
                }
            }
        });
    }
}
