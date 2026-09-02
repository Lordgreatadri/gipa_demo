<?php

namespace Tests\Feature;

use App\Jobs\GenerateCertificateArtifacts;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\District;
use App\Models\Region;
use App\Models\User;
use App\Services\CertificateIntegrityService;
use App\Services\CertificateWorkflowService;
use App\Support\CertificatePermissions;
use Database\Seeders\WorkflowPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CertificateIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private array $keyFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowPermissionSeeder::class);
        $this->configureKey('test-key-1');
    }

    protected function tearDown(): void
    {
        foreach ($this->keyFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_issuance_signs_an_immutable_snapshot_and_records_audit_history(): void
    {
        [$certificate, $issuer] = $this->certificateContext();

        $result = app(CertificateWorkflowService::class)->issue($certificate, $issuer);

        $this->assertSame(Certificate::STATUS_ACTIVE, $result->certificate->status);
        $this->assertSame(hash('sha256', $result->publicToken), $result->certificate->public_token_hash);
        $this->assertSame(CertificateIntegrityService::RESULT_AUTHENTIC, app(CertificateIntegrityService::class)->result($result->certificate));
        $this->assertSame(['issued'], $result->certificate->lifecycleEvents()->pluck('action')->all());
        $this->assertSame(1, Activity::query()->where('subject_type', Certificate::class)->count());

        $this->expectException(LogicException::class);
        $result->certificate->update(['holder_name_snapshot' => 'Altered holder']);
    }

    public function test_payload_tampering_is_detected(): void
    {
        [$certificate, $issuer] = $this->certificateContext();
        $issued = app(CertificateWorkflowService::class)->issue($certificate, $issuer)->certificate;
        $payload = $issued->signed_payload;
        $payload['holder_name'] = 'Counterfeit holder';
        DB::table('certificates')->where('id', $issued->id)->update(['signed_payload' => json_encode($payload)]);

        $this->assertSame(
            CertificateIntegrityService::RESULT_SIGNATURE_INVALID,
            app(CertificateIntegrityService::class)->result($issued->fresh()),
        );
    }

    public function test_retired_public_key_still_verifies_after_active_key_rotation(): void
    {
        [$certificate, $issuer] = $this->certificateContext();
        $issued = app(CertificateWorkflowService::class)->issue($certificate, $issuer)->certificate;
        $this->configureKey('test-key-2', true);

        $this->assertSame('test-key-1', $issued->signing_key_id);
        $this->assertSame(CertificateIntegrityService::RESULT_AUTHENTIC, app(CertificateIntegrityService::class)->result($issued->fresh()));
    }

    public function test_artifact_job_writes_private_qr_and_pdf_files(): void
    {
        Storage::fake('local');
        [$certificate, $issuer] = $this->certificateContext();
        $issued = app(CertificateWorkflowService::class)->issue($certificate, $issuer)->certificate;

        (new GenerateCertificateArtifacts($issued->id))->handle();

        $issued->refresh();
        $this->assertSame(Certificate::ARTIFACT_READY, $issued->artifact_status);
        $this->assertNotNull($issued->artifacts_generated_at);
        Storage::disk('local')->assertExists($issued->qr_code_path);
        Storage::disk('local')->assertExists($issued->pdf_path);
        $this->assertGreaterThan(100, Storage::disk('local')->size($issued->qr_code_path));
        $this->assertGreaterThan(1000, Storage::disk('local')->size($issued->pdf_path));
    }

    public function test_unsupported_algorithm_fails_closed_without_issuing(): void
    {
        [$certificate, $issuer] = $this->certificateContext();
        config()->set('iomp.certificates.algorithm', 'UNAPPROVED');

        try {
            app(CertificateWorkflowService::class)->issue($certificate, $issuer);
            $this->fail('Issuance should reject an unsupported algorithm.');
        } catch (RuntimeException) {
            $this->assertSame(Certificate::STATUS_DRAFT, $certificate->fresh()->status);
            $this->assertDatabaseCount('certificate_lifecycle_events', 0);
        }
    }

    public function test_issued_certificate_cannot_be_deleted(): void
    {
        [$certificate, $issuer] = $this->certificateContext();
        $issued = app(CertificateWorkflowService::class)->issue($certificate, $issuer)->certificate;

        $this->expectException(LogicException::class);
        $issued->delete();
    }

    private function certificateContext(): array
    {
        $issuer = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $issuer->givePermissionTo(CertificatePermissions::ISSUE);
        $region = Region::create(['code' => 'GAR', 'name' => 'Greater Accra']);
        $district = District::create(['region_id' => $region->id, 'code' => 'AMA', 'name' => 'Accra Metropolitan']);
        $type = CertificateType::create(['code' => 'INVESTMENT', 'name' => 'Investment Registration Certificate', 'default_validity_months' => 12]);
        $certificate = Certificate::create([
            'certificate_number' => 'GIPA-CERT-2026-000001',
            'certificate_type_id' => $type->id,
            'district_id' => $district->id,
            'holder_name_snapshot' => 'Akwaaba Industries Limited',
            'organization_name_snapshot' => 'Akwaaba Industries Limited',
            'created_by' => $issuer->id,
            'updated_by' => $issuer->id,
        ]);

        return [$certificate, $issuer];
    }

    private function configureKey(string $keyId, bool $keepExisting = false): void
    {
        $opensslConfig = dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf';
        $this->assertFileExists($opensslConfig, 'The PHP OpenSSL configuration is required to generate ephemeral test keys.');
        $key = openssl_pkey_new([
            'config' => $opensslConfig,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ]);
        $this->assertNotFalse($key, 'Ephemeral OpenSSL key generation failed.');
        $this->assertTrue(openssl_pkey_export($key, $privatePem, null, ['config' => $opensslConfig]));
        $details = openssl_pkey_get_details($key);
        $this->assertNotFalse($details, 'Ephemeral public key export failed.');
        $publicPem = $details['key'];
        $privatePath = tempnam(sys_get_temp_dir(), 'iomp-private-');
        $publicPath = tempnam(sys_get_temp_dir(), 'iomp-public-');
        file_put_contents($privatePath, $privatePem);
        file_put_contents($publicPath, $publicPem);
        $this->keyFiles = [...$this->keyFiles, $privatePath, $publicPath];

        $keys = $keepExisting ? config('iomp.certificates.keys', []) : [];
        $keys[$keyId] = ['private_key_path' => $privatePath, 'public_key_path' => $publicPath];
        config()->set('iomp.certificates.keys', $keys);
        config()->set('iomp.certificates.active_key_id', $keyId);
        config()->set('iomp.certificates.algorithm', 'RSA-SHA256');
    }
}
