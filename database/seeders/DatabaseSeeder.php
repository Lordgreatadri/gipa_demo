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
            GhanaDistrictRegistrySeeder::class,
            IompPrototypeSeeder::class,
        ]);

        if (config('iomp.demo_users.enabled')) {
            $this->call([
                DefaultRoleUserSeeder::class,
                InvestorScenarioSeeder::class,
            ]);
        }
    }
}
