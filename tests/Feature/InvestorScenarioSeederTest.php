<?php

namespace Tests\Feature;

use App\Models\InvestorDocument;
use App\Models\InvestorOnboardingCase;
use App\Models\InvestorProfile;
use App\Models\User;
use Database\Seeders\DefaultSystemUserSeeder;
use Database\Seeders\InvestorKycReferenceSeeder;
use Database\Seeders\InvestorScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestorScenarioSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_one_hundred_and_fifty_complete_investor_scenarios_idempotently(): void
    {
        config()->set('iomp.demo_users', [
            'investor_password' => 'TestInvestorPassword123!',
            'investor_email_pattern' => 'demo.investor%02d@example.test',
            'investor_count' => 150,
        ]);
        config()->set('iomp.default_system_user', [
            'name' => 'Prototype Administrator',
            'email' => 'prototype.admin@example.test',
            'password' => 'AdminPassword123!',
        ]);
        $this->seed([DefaultSystemUserSeeder::class, InvestorKycReferenceSeeder::class]);
        $this->seed(InvestorScenarioSeeder::class);
        $this->seed(InvestorScenarioSeeder::class);

        $this->assertSame(150, User::query()->where('email', 'like', 'demo.investor%@example.test')->count());
        $this->assertSame(150, User::query()->role('Investor')->where('email', 'like', 'demo.investor%@example.test')->count());
        $this->assertSame(150, InvestorProfile::query()->count());
        $this->assertSame(150, InvestorOnboardingCase::query()->count());
        $this->assertSame(7, InvestorOnboardingCase::query()->distinct()->count('status'));
        $this->assertSame(2, InvestorProfile::query()->distinct()->count('profile_type'));
        $this->assertTrue(InvestorOnboardingCase::query()->whereNotNull('assigned_to')->exists());
        $this->assertTrue(InvestorOnboardingCase::query()->where('sla_due_at', '<', now())->exists());
        $this->assertTrue(InvestorOnboardingCase::query()->where('sla_due_at', '>', now())->exists());
        $this->assertTrue(InvestorDocument::query()->where('status', InvestorDocument::STATUS_ACCEPTED)->exists());
        $this->assertTrue(InvestorDocument::query()->where('status', InvestorDocument::STATUS_QUARANTINED)->exists());
        $this->assertTrue(InvestorDocument::query()->where('status', InvestorDocument::STATUS_REJECTED)->exists());
        $this->assertTrue(InvestorDocument::query()->where('status', InvestorDocument::STATUS_EXPIRED)->exists());
        $this->assertSame(InvestorDocument::query()->count(), InvestorDocument::query()->whereHas('media')->count());
    }
}