<?php

namespace Database\Seeders;

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
            CertificateTypeSeeder::class,
            DefaultSystemUserSeeder::class,
            GhanaDistrictRegistrySeeder::class,
            IompPrototypeSeeder::class,
        ]);

        if (config('iomp.demo_users.enabled')) {
            $this->call([
                DefaultRoleUserSeeder::class,
                InvestorScenarioSeeder::class,
                MatchmakingScenarioSeeder::class,
                CertificateScenarioSeeder::class,
            ]);
        }
    }
}
