<?php

namespace Database\Seeders;

use Database\Seeders\WorkflowPermissionSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            WorkflowPermissionSeeder::class,
            DefaultSystemUserSeeder::class,
            GhanaDistrictRegistrySeeder::class,
            IompPrototypeSeeder::class,
        ]);
    }
}
