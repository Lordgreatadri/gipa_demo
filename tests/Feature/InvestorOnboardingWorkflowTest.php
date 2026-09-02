<?php

namespace Tests\Feature;

use App\Models\InvestorDocument;
use App\Models\InvestorDocumentType;
use App\Models\InvestorOnboardingCase;
use App\Models\InvestorProfile;
use App\Models\User;
use App\Services\InvestorOnboardingService;
use App\Support\InvestorPermissions;
use Database\Seeders\InvestorKycReferenceSeeder;
use Database\Seeders\WorkflowPermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class InvestorOnboardingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([WorkflowPermissionSeeder::class, InvestorKycReferenceSeeder::class]);
        Storage::fake('local');
        Notification::fake();
    }

    public function test_required_accepted_kyc_documents_unlock_submission_and_staff_approval(): void
    {
        [$investor, $profile] = $this->investorContext();
        $reviewer = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $reviewer->givePermissionTo(InvestorPermissions::REVIEW, InvestorPermissions::COMPLIANCE_MANAGE);
        $service = app(InvestorOnboardingService::class);
        $case = $service->createDraft($profile, $investor);

        try {
            $service->submit($case, $investor);
            $this->fail('Submission should require accepted KYC evidence.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('documents', $exception->errors());
            $this->assertSame(InvestorOnboardingCase::STATUS_DRAFT, $case->fresh()->status);
        }

        $requiredTypes = InvestorDocumentType::query()
            ->where('is_required', true)
            ->whereNull('applies_to_profile_type')
            ->get();

        foreach ($requiredTypes as $type) {
            $upload = UploadedFile::fake()->create("{$type->code}.pdf", 100, 'application/pdf');
            $document = $case->documents()->create([
                'investor_profile_id' => $profile->id,
                'document_type_id' => $type->id,
                'checksum_sha256' => hash_file('sha256', $upload->getRealPath()),
                'malware_scan_status' => InvestorDocument::SCAN_CLEAN,
                'malware_scanned_at' => now(),
            ]);
            $document->addMedia($upload)->toMediaCollection(InvestorDocument::COLLECTION_FILE);
            $service->acceptDocument($document, $reviewer);
        }

        $submitted = $service->submit($case->fresh(), $investor);
        $reviewing = $service->startReview($submitted, $reviewer);
        $approved = $service->approve($reviewing, $reviewer, 'Identity evidence verified.');

        $this->assertSame(InvestorOnboardingCase::STATUS_APPROVED, $approved->status);
        $this->assertSame(InvestorProfile::ONBOARDING_VERIFIED, $profile->fresh()->onboarding_state);
        $this->assertNotNull($profile->fresh()->onboarded_at);
        $this->assertSame(['created', 'submit', 'start_review', 'approve'], $approved->events()->orderBy('id')->pluck('action')->all());
        $this->assertSame(3, Activity::query()->where('subject_type', InvestorOnboardingCase::class)->count());
        $this->assertDatabaseCount('media', $requiredTypes->count());
        $this->assertDatabaseMissing('media', ['model_type' => InvestorDocument::class, 'disk' => 'public']);
    }

    public function test_investor_cannot_access_another_investors_onboarding_case(): void
    {
        [$owner, $profile] = $this->investorContext();
        [$other] = $this->investorContext();
        $case = app(InvestorOnboardingService::class)->createDraft($profile, $owner);

        $this->expectException(AuthorizationException::class);

        app(InvestorOnboardingService::class)->submit($case, $other);
    }

    private function investorContext(): array
    {
        $investor = User::factory()->create(['account_type' => User::ACCOUNT_INVESTOR]);
        $profile = $investor->investorProfile()->create([
            'display_name' => $investor->name,
            'created_by' => $investor->id,
            'updated_by' => $investor->id,
        ]);

        return [$investor, $profile];
    }
}
