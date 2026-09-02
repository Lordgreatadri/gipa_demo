<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\District;
use App\Models\Region;
use App\Models\User;
use App\Notifications\CertificateVerificationAlert;
use App\Services\CertificateWorkflowService;
use App\Support\CertificatePermissions;
use Database\Seeders\WorkflowPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CertificateVerificationHttpTest extends TestCase
{
    use RefreshDatabase;

    private array $keyFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowPermissionSeeder::class);
        $this->configureKey();
    }

    protected function tearDown(): void
    {
        foreach ($this->keyFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_public_verification_exposes_only_the_approved_online_snapshot(): void
    {
        [$certificate, $token] = $this->issuedCertificate();

        $this->get(route('certificates.verify', $token))
            ->assertOk()
            ->assertSee('Authentic and active')
            ->assertSee($certificate->certificate_number)
            ->assertSee('Akwaaba Industries Limited')
            ->assertDontSee('digital_signature')
            ->assertDontSee('idempotency_key');

        $this->getJson(route('api.v1.certificates.verify', $token))
            ->assertOk()
            ->assertJsonPath('data.result', 'authentic')
            ->assertJsonMissingPath('data.digital_signature')
            ->assertJsonMissingPath('data.officer');

        $unknown = str_repeat('A', 64);
        $this->getJson(route('api.v1.certificates.verify', $unknown))
            ->assertNotFound()
            ->assertExactJson(['data' => ['result' => 'not_found', 'checked_at' => now()->toIso8601String()]]);
        $this->getJson(route('api.v1.certificates.verify', 'malformed-token'))
            ->assertNotFound()
            ->assertExactJson(['data' => ['result' => 'not_found', 'checked_at' => now()->toIso8601String()]]);
    }

    public function test_assigned_officer_can_verify_once_and_other_district_is_denied(): void
    {
        [$certificate] = $this->issuedCertificate();
        $officer = $this->districtOfficer();
        $otherOfficer = $this->districtOfficer();
        $officer->districtAssignments()->create([
            'district_id' => $certificate->district_id,
            'starts_at' => now()->subDay(),
        ]);
        $key = (string) Str::uuid();
        $payload = ['officer_decision' => 'valid', 'idempotency_key' => $key, 'notes' => 'Physical certificate matched the registry.'];

        $this->actingAs($officer)->get(route('staff.certificates.show', $certificate))->assertOk();
        $this->actingAs($otherOfficer)->get(route('staff.certificates.show', $certificate))->assertForbidden();

        $this->actingAs($officer)->post(route('staff.certificates.verify', $certificate), $payload)->assertRedirect();
        $this->actingAs($officer)->post(route('staff.certificates.verify', $certificate), $payload)->assertRedirect();

        $this->assertDatabaseCount('certificate_verifications', 1);
        $this->assertDatabaseHas('certificate_verifications', [
            'certificate_id' => $certificate->id,
            'officer_id' => $officer->id,
            'district_id' => $certificate->district_id,
            'system_result' => 'authentic',
            'officer_decision' => 'valid',
            'idempotency_key' => $key,
        ]);
    }

    public function test_certificate_registry_is_permission_scoped_paginated_and_query_bounded(): void
    {
        [$certificate] = $this->issuedCertificate();
        $this->issuedCertificate(now()->startOfYear()->addMonth(), $certificate->district);
        $otherRegion = Region::firstOrCreate(['code' => 'ASH'], ['name' => 'Ashanti']);
        $otherDistrict = District::firstOrCreate(['code' => 'KMA'], ['region_id' => $otherRegion->id, 'name' => 'Kumasi Metropolitan']);
        $this->issuedCertificate(now()->startOfYear()->addMonths(4), $otherDistrict);
        $officer = $this->districtOfficer();
        $officer->districtAssignments()->create(['district_id' => $certificate->district_id, 'starts_at' => now()->subDay()]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($officer)->get(route('staff.certificates.index'))
            ->assertOk()
            ->assertSee($certificate->certificate_number)
            ->assertSee('Certificate registry list')
            ->assertDontSee('Regional registry coverage');

        $this->assertLessThanOrEqual(12, count(DB::getQueryLog()));

        DB::flushQueryLog();
        $this->actingAs($officer)->get(route('staff.certificates.overview'))
            ->assertOk()
            ->assertSee('Certificate overview')
            ->assertSee('Registry total')
            ->assertSee('Regional registry coverage')
            ->assertSee('Validity composition')
            ->assertSee('Expired by quarter')
            ->assertSee('Greater Accra total 2, active 1')
            ->assertSee('Draft 0, Active 1, Expired 1, Suspended 0, Revoked 0, Other 0')
            ->assertSee('Q1 1, Q2 0, Q3 0, Q4 0')
            ->assertDontSee('Ashanti');

        $this->assertLessThanOrEqual(12, count(DB::getQueryLog()));
        $this->assertTrue(Role::findByName('District Officer')->hasPermissionTo(CertificatePermissions::VERIFY));
        $this->assertFalse(Role::findByName('Field Agent')->hasPermissionTo(CertificatePermissions::VIEW));
    }

    public function test_suspicious_field_decision_alerts_certificate_auditors(): void
    {
        Notification::fake();
        [$certificate] = $this->issuedCertificate();
        $officer = $this->districtOfficer();
        $officer->districtAssignments()->create(['district_id' => $certificate->district_id, 'starts_at' => now()->subDay()]);
        $auditor = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $auditor->givePermissionTo(CertificatePermissions::AUDIT_VIEW, CertificatePermissions::VIEW);
        $idempotencyKey = (string) Str::uuid();
        $payload = [
            'officer_decision' => 'suspicious',
            'idempotency_key' => $idempotencyKey,
            'notes' => 'Physical security mark differs from the issued document.',
        ];

        $this->actingAs($officer)->post(route('staff.certificates.verify', $certificate), $payload)->assertRedirect();
        $this->actingAs($officer)->post(route('staff.certificates.verify', $certificate), $payload)->assertRedirect();

        Notification::assertSentTo($auditor, CertificateVerificationAlert::class);
        Notification::assertNotSentTo($officer, CertificateVerificationAlert::class);
        Notification::assertCount(1);
    }

    private function issuedCertificate(?\DateTimeInterface $expiresAt = null, ?District $district = null): array
    {
        $issuer = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $issuer->givePermissionTo(CertificatePermissions::ISSUE);
        if (! $district) {
            $region = Region::firstOrCreate(['code' => 'GAR'], ['name' => 'Greater Accra']);
            $district = District::firstOrCreate(['code' => 'AMA'], ['region_id' => $region->id, 'name' => 'Accra Metropolitan']);
        }
        $type = CertificateType::firstOrCreate(['code' => 'INVESTMENT_REGISTRATION'], ['name' => 'Investment Registration Certificate']);
        $certificate = Certificate::create([
            'certificate_number' => 'GIPA-CERT-'.Str::upper(Str::random(12)),
            'certificate_type_id' => $type->id,
            'district_id' => $district->id,
            'holder_name_snapshot' => 'Akwaaba Industries Limited',
            'organization_name_snapshot' => 'Akwaaba Industries Limited',
            'expires_at' => $expiresAt,
            'created_by' => $issuer->id,
            'updated_by' => $issuer->id,
        ]);
        $result = app(CertificateWorkflowService::class)->issue($certificate, $issuer);

        return [$result->certificate, $result->publicToken];
    }

    private function districtOfficer(): User
    {
        $officer = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $officer->assignRole('District Officer');

        return $officer;
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
        config()->set('iomp.certificates.active_key_id', 'http-test-key');
        config()->set('iomp.certificates.algorithm', 'RSA-SHA256');
        config()->set('iomp.certificates.keys', [
            'http-test-key' => ['private_key_path' => $privatePath, 'public_key_path' => $publicPath],
        ]);
    }
}
