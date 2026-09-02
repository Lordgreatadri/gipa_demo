<?php

namespace Tests\Feature;

use App\Jobs\GenerateCertificateArtifacts;
use App\Models\Certificate;
use App\Models\CertificateLifecycleEvent;
use App\Models\CertificateVerification;
use App\Models\InvestorProfile;
use App\Models\StaffDistrictAssignment;
use App\Models\User;
use App\Notifications\CertificateVerificationAlert;
use App\Services\CertificateIntegrityService;
use Database\Seeders\CertificateScenarioSeeder;
use Database\Seeders\CertificateTypeSeeder;
use Database\Seeders\DefaultRoleUserSeeder;
use Database\Seeders\GhanaDistrictRegistrySeeder;
use Database\Seeders\InvestorKycReferenceSeeder;
use Database\Seeders\InvestorScenarioSeeder;
use Database\Seeders\IompPrototypeSeeder;
use Database\Seeders\WorkflowPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CertificateScenarioSeederTest extends TestCase
{
    use RefreshDatabase;

    private array $keyFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Notification::fake();
        $this->configureDemoUsers();
        $this->configureKey();
    }

    protected function tearDown(): void
    {
        foreach ($this->keyFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_it_seeds_full_certificate_registry_scenarios_for_demo_investors_idempotently(): void
    {
        $this->seed([
            WorkflowPermissionSeeder::class,
            InvestorKycReferenceSeeder::class,
            CertificateTypeSeeder::class,
            GhanaDistrictRegistrySeeder::class,
            IompPrototypeSeeder::class,
            DefaultRoleUserSeeder::class,
            InvestorScenarioSeeder::class,
        ]);
        $this->seed(CertificateScenarioSeeder::class);
        $this->seed(CertificateScenarioSeeder::class);

        $officer = User::query()->where('email', 'district.officer@example.test')->firstOrFail();
        $demoCertificates = Certificate::query()->where('certificate_number', 'like', 'GIPA-DEMO-CERT-%');

        $this->assertSame(150, $demoCertificates->count());
        $this->assertSame(150, InvestorProfile::query()->whereHas('certificates', fn ($query) => $query->where('certificate_number', 'like', 'GIPA-DEMO-CERT-%'))->count());
        $this->assertSame(6, (clone $demoCertificates)->where('status', Certificate::STATUS_DRAFT)->count());
        $this->assertSame(144, (clone $demoCertificates)->whereNotNull('digital_signature')->count());
        $this->assertTrue((clone $demoCertificates)->where('status', Certificate::STATUS_ACTIVE)->where('expires_at', '<', now())->exists());
        $this->assertTrue((clone $demoCertificates)->where('status', Certificate::STATUS_ACTIVE)->whereBetween('expires_at', [now(), now()->addDays(30)])->exists());
        $this->assertTrue((clone $demoCertificates)->where('status', Certificate::STATUS_SUSPENDED)->exists());
        $this->assertTrue((clone $demoCertificates)->where('status', Certificate::STATUS_REVOKED)->exists());
        $this->assertSame(48, CertificateVerification::query()->where('idempotency_key', 'like', 'demo-certificate-verification-%')->count());
        $this->assertTrue(CertificateVerification::query()->where('officer_decision', CertificateVerification::DECISION_VALID)->exists());
        $this->assertTrue(CertificateVerification::query()->where('officer_decision', CertificateVerification::DECISION_SUSPICIOUS)->exists());
        $this->assertTrue(CertificateVerification::query()->where('system_result', CertificateIntegrityService::RESULT_EXPIRED)->exists());
        $this->assertSame(144, CertificateLifecycleEvent::query()->where('action', 'issued')->count());
        $auditorCount = User::query()->permission('certificates.audit.view')->whereKeyNot($officer->id)->count();
        $this->assertSame(4 * $auditorCount, User::query()->get()->sum(fn (User $user) => $user->notifications()->where('type', CertificateVerificationAlert::class)->count()));
        $this->assertSame(
            (clone $demoCertificates)->distinct()->count('district_id'),
            StaffDistrictAssignment::query()->where('user_id', $officer->id)->whereNull('ends_at')->distinct()->count('district_id')
        );
        Queue::assertPushed(GenerateCertificateArtifacts::class, 144);
    }

    private function configureDemoUsers(): void
    {
        config()->set('iomp.demo_users', [
            'enabled' => true,
            'staff_password' => 'TestStaffPassword123!',
            'investor_password' => 'TestInvestorPassword123!',
            'investor_email_pattern' => 'demo.investor%02d@example.test',
            'investor_count' => 150,
            'roles' => [
                ['role' => 'Super Administrator', 'name' => 'Demo Administrator', 'email' => 'admin@example.test', 'type' => 'staff'],
                ['role' => 'Content / Data Manager', 'name' => 'Demo Content Manager', 'email' => 'content@example.test', 'type' => 'staff'],
                ['role' => 'District Officer', 'name' => 'Demo District Officer', 'email' => 'district.officer@example.test', 'type' => 'staff'],
                ['role' => 'Field Agent', 'name' => 'Demo Field Agent', 'email' => 'field@example.test', 'type' => 'staff'],
                ['role' => 'Reviewer / Approver', 'name' => 'Demo Reviewer', 'email' => 'reviewer@example.test', 'type' => 'staff'],
                ['role' => 'Investor', 'name' => 'Demo Investor', 'email' => 'demo.investor01@example.test', 'type' => 'investor'],
            ],
        ]);
    }

    private function configureKey(): void
    {
        $opensslConfig = dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf';
        $this->assertFileExists($opensslConfig);
        $key = openssl_pkey_new(['config' => $opensslConfig, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $this->assertNotFalse($key);
        $this->assertTrue(openssl_pkey_export($key, $privatePem, null, ['config' => $opensslConfig]));
        $details = openssl_pkey_get_details($key);
        $this->assertNotFalse($details);
        $privatePath = tempnam(sys_get_temp_dir(), 'iomp-private-');
        $publicPath = tempnam(sys_get_temp_dir(), 'iomp-public-');
        file_put_contents($privatePath, $privatePem);
        file_put_contents($publicPath, $details['key']);
        $this->keyFiles = [$privatePath, $publicPath];
        config()->set('iomp.certificates.active_key_id', 'seeder-test-key');
        config()->set('iomp.certificates.algorithm', 'RSA-SHA256');
        config()->set('iomp.certificates.keys', [
            'seeder-test-key' => ['private_key_path' => $privatePath, 'public_key_path' => $publicPath],
        ]);
    }
}
