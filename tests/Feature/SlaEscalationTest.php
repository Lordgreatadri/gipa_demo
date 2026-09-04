<?php

namespace Tests\Feature;

use App\Models\InvestorOnboardingCase;
use App\Models\InvestorProfile;
use App\Models\User;
use App\Notifications\SlaBreachEscalation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SlaEscalationTest extends TestCase
{
    use RefreshDatabase;

    private function breachedCase(User $reviewer): InvestorOnboardingCase
    {
        $investor = User::factory()->create(['account_type' => User::ACCOUNT_INVESTOR]);
        $profile = InvestorProfile::create(['user_id' => $investor->id, 'display_name' => 'Ama Mensah']);

        return InvestorOnboardingCase::create([
            'reference' => 'ONB-2026-9001',
            'investor_profile_id' => $profile->id,
            'status' => InvestorOnboardingCase::STATUS_UNDER_REVIEW,
            'assigned_to' => $reviewer->id,
            'sla_due_at' => now()->subHours(3),
        ]);
    }

    public function test_breached_items_escalate_once_to_the_assigned_reviewer(): void
    {
        Notification::fake();

        $reviewer = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $case = $this->breachedCase($reviewer);

        $this->artisan('sla:escalate')->assertSuccessful();

        Notification::assertSentTo($reviewer, SlaBreachEscalation::class);
        $this->assertNotNull($case->fresh()->sla_escalated_at);

        // A second run must not re-notify for the same breach.
        Notification::fake();
        $this->artisan('sla:escalate')->assertSuccessful();
        Notification::assertNothingSent();
    }

    public function test_items_within_the_sla_window_are_not_escalated(): void
    {
        Notification::fake();

        $reviewer = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $case = $this->breachedCase($reviewer);
        $case->update(['sla_due_at' => now()->addDay()]);

        $this->artisan('sla:escalate')->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertNull($case->fresh()->sla_escalated_at);
    }
}
