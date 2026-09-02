<?php

namespace Tests\Feature;

use App\Models\InvestorOnboardingCase;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_baseline_seeding_skips_demo_data_when_not_explicitly_enabled(): void
    {
        config()->set('iomp.default_system_user', [
            'name' => 'Configured Administrator',
            'email' => 'configured-admin@example.test',
            'password' => 'EnvironmentOnly!Password42',
        ]);
        config()->set('iomp.demo_users', [
            'enabled' => false,
            'staff_password' => null,
            'investor_password' => null,
            'investor_email_pattern' => null,
            'investor_count' => 150,
            'roles' => [],
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::query()->count());
        $this->assertSame(0, InvestorOnboardingCase::query()->count());
        $this->assertDatabaseHas('users', ['email' => 'configured-admin@example.test']);
        $this->assertDatabaseHas('sectors', ['code' => 'AGR']);
    }
}