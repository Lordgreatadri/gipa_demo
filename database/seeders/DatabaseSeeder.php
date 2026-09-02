<?php

namespace Database\Seeders;

use Database\Seeders\DefaultRoleUserSeeder;
use Database\Seeders\InvestorKycReferenceSeeder;
use Database\Seeders\InvestorScenarioSeeder;
use Database\Seeders\WorkflowPermissionSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            WorkflowPermissionSeeder::class,
            InvestorKycReferenceSeeder::class,
            DefaultSystemUserSeeder::class,
            DefaultRoleUserSeeder::class,
            GhanaDistrictRegistrySeeder::class,
            IompPrototypeSeeder::class,
            InvestorScenarioSeeder::class,
        ]);
    }
}
