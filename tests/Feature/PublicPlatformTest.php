<?php

namespace Tests\Feature;

use App\Models\InvestorDocument;
use App\Models\InvestorDocumentType;
use App\Models\InvestorOnboardingCase;
use App\Models\InvestorProfile;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_identifies_the_platform(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Investment Opportunities Mapping Project')
            ->assertSee('manifest.webmanifest');
    }

    public function test_guest_can_access_investor_login_and_public_guide(): void
    {
        $this->get(route('login'))
            ->assertOk();

        $this->get(route('platform.guide'))
            ->assertOk()
            ->assertSee('Investor user guide')
            ->assertSee('Complete secure KYC onboarding')
            ->assertDontSee('/staff/login');
    }

    public function test_public_navigation_replaces_login_with_the_investor_workspace_after_authentication(): void
    {
        $investor = User::factory()->create([
            'account_type' => User::ACCOUNT_INVESTOR,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($investor)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Investor workspace')
            ->assertDontSee('Investor login');
    }

    public function test_staff_guide_requires_a_staff_account(): void
    {
        $this->get(route('staff.guide'))
            ->assertRedirect(route('login'));

        $investor = User::factory()->create(['account_type' => User::ACCOUNT_INVESTOR]);
        $this->actingAs($investor)
            ->get(route('staff.guide'))
            ->assertForbidden();

        $staff = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $this->actingAs($staff)
            ->get(route('staff.guide'))
            ->assertOk()
            ->assertSee('/staff/login')
            ->assertSee('Staff user guide');
    }

    public function test_verified_investor_portal_uses_authenticated_workspace_navigation(): void
    {
        $investor = User::factory()->create([
            'account_type' => User::ACCOUNT_INVESTOR,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($investor)
            ->get(route('investor.dashboard'))
            ->assertOk()
            ->assertSee('Secure investor portal')
            ->assertSee('Opportunity matches')
            ->assertDontSee('Primary navigation');
    }

    public function test_investor_portal_charts_review_statuses_and_lists_only_active_sectors(): void
    {
        $investor = User::factory()->create([
            'account_type' => User::ACCOUNT_INVESTOR,
            'email_verified_at' => now(),
        ]);
        $profile = InvestorProfile::create([
            'user_id' => $investor->id,
            'display_name' => 'Ama Mensah',
        ]);
        $case = InvestorOnboardingCase::create([
            'reference' => 'ONB-2026-0001',
            'investor_profile_id' => $profile->id,
        ]);
        $type = InvestorDocumentType::create(['code' => 'IDENTITY', 'name' => 'Identity document']);
        foreach ([InvestorDocument::STATUS_QUARANTINED, InvestorDocument::STATUS_EXPIRED] as $status) {
            InvestorDocument::create([
                'investor_profile_id' => $profile->id,
                'investor_onboarding_case_id' => $case->id,
                'document_type_id' => $type->id,
                'status' => $status,
                'checksum_sha256' => str_repeat('a', 64),
            ]);
        }
        $activeSector = Sector::create(['code' => 'AGR', 'name' => 'Agriculture', 'is_active' => true]);
        $inactiveSector = Sector::create(['code' => 'OLD', 'name' => 'Retired sector', 'is_active' => false]);

        $response = $this->actingAs($investor)->get(route('investor.dashboard'))->assertOk();

        $this->assertSame(
            ['Quarantined', 'Accepted', 'Rejected', 'Expired'],
            $response->viewData('evidenceChart')['labels']->all(),
        );
        $this->assertSame([1, 0, 0, 1], $response->viewData('evidenceChart')['datasets'][0]['data']->all());
        $this->assertTrue($response->viewData('sectors')->contains($activeSector));
        $this->assertFalse($response->viewData('sectors')->contains($inactiveSector));
    }

    public function test_health_endpoint_reports_service_availability(): void
    {
        $this->getJson(route('health'))
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('service', 'iomp-web');
    }

    public function test_pwa_public_assets_exist(): void
    {
        $this->assertFileExists(public_path('manifest.webmanifest'));
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('icons/iomp-icon.svg'));
        $serviceWorker = file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString("'/api'", $serviceWorker);
        $this->assertStringContainsString("'/c/'", $serviceWorker);
        $this->assertStringContainsString("'/portal'", $serviceWorker);
        $this->assertStringContainsString("'/staff'", $serviceWorker);
    }
}
