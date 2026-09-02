<?php

namespace Tests\Feature;

use App\Models\InvestorDocument;
use App\Models\InvestorDocumentType;
use App\Models\User;
use App\Services\InvestorOnboardingService;
use App\Support\InvestorPermissions;
use Database\Seeders\InvestorKycReferenceSeeder;
use Database\Seeders\WorkflowPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvestorOnboardingHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([WorkflowPermissionSeeder::class, InvestorKycReferenceSeeder::class]);
        Storage::fake('local');
    }

    public function test_registration_creates_exactly_one_investor_profile(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Create secure account')
            ->assertSee('password_confirmation');

        $response = $this->post(route('register.store'), [
            'name' => 'Ama Mensah',
            'email' => 'ama@example.com',
            'organization' => 'Mensah Ventures',
            'phone' => '+233 20 000 0000',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
        ]);

        $user = User::query()->where('email', 'ama@example.com')->firstOrFail();

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('investor_profiles', [
            'user_id' => $user->id,
            'display_name' => 'Ama Mensah',
        ]);
        $this->assertSame(1, $user->investorProfile()->count());
    }

    public function test_profile_backfill_is_bounded_and_idempotent(): void
    {
        User::factory()->count(3)->create(['account_type' => User::ACCOUNT_INVESTOR]);
        User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);

        $this->artisan('investors:backfill-profiles', ['--chunk' => 2])
            ->expectsOutput('Created 3 investor profile(s).')
            ->assertSuccessful();
        $this->artisan('investors:backfill-profiles', ['--chunk' => 2])
            ->expectsOutput('Created 0 investor profile(s).')
            ->assertSuccessful();

        $this->assertDatabaseCount('investor_profiles', 3);
    }

    public function test_verified_investor_can_upload_private_kyc_evidence_only_to_own_case(): void
    {
        [$investor, $case] = $this->investorCase();
        [$other, $otherCase] = $this->investorCase();
        $type = InvestorDocumentType::query()->where('code', 'proof_of_address')->firstOrFail();

        $response = $this->actingAs($investor)->post(route('investor.onboarding.documents.store', $case), [
            'document_type' => $type->code,
            'document' => UploadedFile::fake()->create('address.pdf', 120, 'application/pdf'),
        ]);

        $response->assertRedirect(route('investor.dashboard'));
        $document = InvestorDocument::query()->firstOrFail();
        $this->assertSame(InvestorDocument::STATUS_QUARANTINED, $document->status);
        $this->assertSame(InvestorDocument::SCAN_PENDING, $document->malware_scan_status);
        $this->assertSame('local', $document->getFirstMedia(InvestorDocument::COLLECTION_FILE)?->disk);

        $this->actingAs($other)
            ->get(route('investor.documents.download', $document))
            ->assertForbidden();

        $this->actingAs($investor)
            ->post(route('investor.onboarding.documents.store', $otherCase), [
                'document_type' => $type->code,
                'document' => UploadedFile::fake()->create('other.pdf', 120, 'application/pdf'),
            ])
            ->assertForbidden();
    }

    public function test_staff_queue_and_document_decisions_require_explicit_permissions(): void
    {
        [$investor, $case] = $this->investorCase();
        $staff = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $type = InvestorDocumentType::query()->where('code', 'proof_of_address')->firstOrFail();
        $upload = UploadedFile::fake()->create('address.pdf', 120, 'application/pdf');
        $document = $case->documents()->create([
            'investor_profile_id' => $case->investor_profile_id,
            'document_type_id' => $type->id,
            'checksum_sha256' => hash_file('sha256', $upload->getRealPath()),
        ]);
        $document->addMedia($upload)->toMediaCollection(InvestorDocument::COLLECTION_FILE);

        $this->actingAs($staff)->get(route('staff.investors.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('staff.investor-documents.decision', [$document, 'accept']))->assertForbidden();

        $staff->givePermissionTo(InvestorPermissions::VIEW, InvestorPermissions::COMPLIANCE_MANAGE);

        $this->actingAs($staff)->get(route('staff.investors.index'))->assertOk()->assertSee($case->reference);
        $this->actingAs($staff)
            ->post(route('staff.investor-documents.decision', [$document, 'accept']))
            ->assertRedirect(route('staff.investors.show', $case));
        $this->assertSame(InvestorDocument::STATUS_ACCEPTED, $document->fresh()->status);
        $this->assertSame(InvestorDocument::SCAN_CLEAN, $document->fresh()->malware_scan_status);
    }

    private function investorCase(): array
    {
        $investor = User::factory()->create([
            'account_type' => User::ACCOUNT_INVESTOR,
            'email_verified_at' => now(),
        ]);
        $profile = $investor->investorProfile()->create([
            'display_name' => $investor->name,
            'created_by' => $investor->id,
            'updated_by' => $investor->id,
        ]);
        $case = app(InvestorOnboardingService::class)->createDraft($profile, $investor);

        return [$investor, $case];
    }
}
