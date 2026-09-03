<?php

namespace Database\Seeders;

use App\Models\InvestorDocument;
use App\Models\InvestorDocumentType;
use App\Models\InvestorOnboardingCase;
use App\Models\InvestorProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class InvestorScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $configuration = Validator::make(config('iomp.demo_users'), [
            'investor_password' => ['required', 'string', Password::min(12)->mixedCase()->letters()->numbers()->symbols()],
            'investor_email_pattern' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if (preg_match_all('/%(?:0?[1-9]\d*)?d/', $value) !== 1 || substr_count($value, '%') !== 1) {
                    $fail("The {$attribute} must contain exactly one integer placeholder, such as %02d.");
                }
            }],
            'investor_count' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        if ($configuration->fails()) {
            throw new ValidationException($configuration);
        }

        $configuration = $configuration->validated();
        $documentTypes = InvestorDocumentType::query()->orderBy('sort_order')->get();
        $investorRole = Role::findOrCreate('Investor', 'web');
        $reviewers = User::query()
            ->where('account_type', User::ACCOUNT_STAFF)
            ->where('status', User::STATUS_ACTIVE)
            ->orderBy('id')
            ->get();
        $statuses = [
            InvestorOnboardingCase::STATUS_DRAFT,
            InvestorOnboardingCase::STATUS_SUBMITTED,
            InvestorOnboardingCase::STATUS_UNDER_REVIEW,
            InvestorOnboardingCase::STATUS_ACTION_REQUIRED,
            InvestorOnboardingCase::STATUS_APPROVED,
            InvestorOnboardingCase::STATUS_REJECTED,
            InvestorOnboardingCase::STATUS_WITHDRAWN,
        ];
        $countries = ['GH', 'NG', 'GB', 'US', 'DE', 'ZA', 'KE', 'CA', 'FR', 'AE'];
        $firstNames = ['Ama', 'Kwame', 'Akosua', 'Kojo', 'Abena', 'Kofi', 'Adwoa', 'Yaw', 'Esi', 'Nana'];
        $lastNames = ['Mensah', 'Owusu', 'Boateng', 'Asante', 'Osei', 'Agyeman', 'Darko', 'Addo', 'Badu', 'Quartey'];

        DB::transaction(function () use ($configuration, $documentTypes, $investorRole, $reviewers, $statuses, $countries, $firstNames, $lastNames): void {
            for ($index = 1; $index <= $configuration['investor_count']; $index++) {
                $name = $firstNames[($index - 1) % count($firstNames)].' '.$lastNames[(int) floor(($index - 1) / count($firstNames)) % count($lastNames)];
                $email = sprintf($configuration['investor_email_pattern'], $index);
                $profileType = $index % 2 === 0 ? InvestorProfile::TYPE_ORGANIZATION_REPRESENTATIVE : InvestorProfile::TYPE_INDIVIDUAL;
                $status = $statuses[($index - 1) % count($statuses)];
                $submitted = ! in_array($status, [InvestorOnboardingCase::STATUS_DRAFT], true);
                $reviewStarted = in_array($status, [InvestorOnboardingCase::STATUS_UNDER_REVIEW, InvestorOnboardingCase::STATUS_ACTION_REQUIRED, InvestorOnboardingCase::STATUS_APPROVED, InvestorOnboardingCase::STATUS_REJECTED], true);
                $decided = in_array($status, [InvestorOnboardingCase::STATUS_APPROVED, InvestorOnboardingCase::STATUS_REJECTED, InvestorOnboardingCase::STATUS_WITHDRAWN], true);

                $user = User::query()->updateOrCreate(['email' => $email], [
                    'name' => $name,
                    'organization' => $profileType === InvestorProfile::TYPE_ORGANIZATION_REPRESENTATIVE ? "Demo Venture {$index} Ltd" : null,
                    'phone' => sprintf('+233 20 555 %04d', $index),
                    'email_verified_at' => now()->subDays(($index % 90) + 1),
                    'account_type' => User::ACCOUNT_INVESTOR,
                    'status' => $index % 17 === 0 ? User::STATUS_SUSPENDED : User::STATUS_ACTIVE,
                    'password' => Hash::make($configuration['investor_password']),
                ]);
                $user->syncRoles([$investorRole]);

                $profile = InvestorProfile::withTrashed()->updateOrCreate(['user_id' => $user->id], [
                    'profile_type' => $profileType,
                    'display_name' => $name,
                    'country_code' => $countries[($index - 1) % count($countries)],
                    'nationality_country_code' => $countries[$index % count($countries)],
                    'preferred_contact_channel' => $index % 3 === 0 ? 'phone' : 'email',
                    'onboarding_state' => $this->profileState($status),
                    'status' => $user->status === User::STATUS_SUSPENDED ? InvestorProfile::STATUS_SUSPENDED : InvestorProfile::STATUS_ACTIVE,
                    'last_engaged_at' => now()->subDays($index % 21),
                    'onboarded_at' => $status === InvestorOnboardingCase::STATUS_APPROVED ? now()->subDays($index % 30) : null,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'deleted_at' => null,
                ]);

                $reviewer = $reviewers->isNotEmpty() && $reviewStarted ? $reviewers[($index - 1) % $reviewers->count()] : null;
                $case = InvestorOnboardingCase::query()->updateOrCreate(['reference' => sprintf('ONB-DEMO-%04d', $index)], [
                    'investor_profile_id' => $profile->id,
                    'status' => $status,
                    'assigned_to' => $reviewer?->id,
                    'submitted_at' => $submitted ? now()->subDays(($index % 25) + 2) : null,
                    'review_started_at' => $reviewStarted ? now()->subDays(($index % 18) + 1) : null,
                    'decided_at' => $decided ? now()->subDays($index % 12) : null,
                    'sla_due_at' => in_array($status, [InvestorOnboardingCase::STATUS_SUBMITTED, InvestorOnboardingCase::STATUS_UNDER_REVIEW], true)
                        ? ($index % 3 === 0 ? now()->subDays(($index % 5) + 1) : now()->addDays(($index % 6) + 1))
                        : null,
                    'decision_reason' => match ($status) {
                        InvestorOnboardingCase::STATUS_ACTION_REQUIRED => 'Please provide a clearer proof of address and confirm the investment representative.',
                        InvestorOnboardingCase::STATUS_APPROVED => 'Identity and required evidence verified for the prototype.',
                        InvestorOnboardingCase::STATUS_REJECTED => 'Submitted evidence could not be verified.',
                        InvestorOnboardingCase::STATUS_WITHDRAWN => 'Application withdrawn by the investor.',
                        default => null,
                    },
                    'created_by' => $user->id,
                    'updated_by' => $reviewer?->id ?? $user->id,
                    'version' => $reviewStarted ? 3 : ($submitted ? 2 : 1),
                ]);

                $this->seedEvents($case, $user, $reviewer);
                $this->seedDocuments($case, $documentTypes, $reviewer, $index);
            }
        });
    }

    private function profileState(string $status): string
    {
        return match ($status) {
            InvestorOnboardingCase::STATUS_DRAFT, InvestorOnboardingCase::STATUS_WITHDRAWN, InvestorOnboardingCase::STATUS_REJECTED => InvestorProfile::ONBOARDING_IN_PROGRESS,
            InvestorOnboardingCase::STATUS_SUBMITTED, InvestorOnboardingCase::STATUS_UNDER_REVIEW => InvestorProfile::ONBOARDING_SUBMITTED,
            InvestorOnboardingCase::STATUS_ACTION_REQUIRED => InvestorProfile::ONBOARDING_ACTION_REQUIRED,
            InvestorOnboardingCase::STATUS_APPROVED => InvestorProfile::ONBOARDING_VERIFIED,
        };
    }

    private function seedEvents(InvestorOnboardingCase $case, User $investor, ?User $reviewer): void
    {
        $events = [['created', null, InvestorOnboardingCase::STATUS_DRAFT, $investor, null]];
        if ($case->status !== InvestorOnboardingCase::STATUS_DRAFT) {
            $events[] = ['submit', InvestorOnboardingCase::STATUS_DRAFT, InvestorOnboardingCase::STATUS_SUBMITTED, $investor, null];
        }
        if (in_array($case->status, [InvestorOnboardingCase::STATUS_UNDER_REVIEW, InvestorOnboardingCase::STATUS_ACTION_REQUIRED, InvestorOnboardingCase::STATUS_APPROVED, InvestorOnboardingCase::STATUS_REJECTED], true)) {
            $events[] = ['start_review', InvestorOnboardingCase::STATUS_SUBMITTED, InvestorOnboardingCase::STATUS_UNDER_REVIEW, $reviewer, null];
        }
        $terminal = match ($case->status) {
            InvestorOnboardingCase::STATUS_ACTION_REQUIRED => ['request_changes', InvestorOnboardingCase::STATUS_UNDER_REVIEW],
            InvestorOnboardingCase::STATUS_APPROVED => ['approve', InvestorOnboardingCase::STATUS_UNDER_REVIEW],
            InvestorOnboardingCase::STATUS_REJECTED => ['reject', InvestorOnboardingCase::STATUS_UNDER_REVIEW],
            InvestorOnboardingCase::STATUS_WITHDRAWN => ['withdraw', InvestorOnboardingCase::STATUS_SUBMITTED],
            default => null,
        };
        if ($terminal) {
            $events[] = [$terminal[0], $terminal[1], $case->status, $reviewer ?? $investor, $case->decision_reason];
        }

        foreach ($events as $position => [$action, $from, $to, $actor, $reason]) {
            $case->events()->firstOrCreate(['action' => $action], [
                'actor_id' => $actor?->id,
                'from_status' => $from,
                'to_status' => $to,
                'reason' => $reason,
                'metadata' => ['schema_version' => 1, 'source' => 'prototype_seeder'],
                'occurred_at' => $case->created_at->copy()->addHours($position * 6),
            ]);
        }
    }

    private function seedDocuments(InvestorOnboardingCase $case, $documentTypes, ?User $reviewer, int $index): void
    {
        $count = match ($case->status) {
            InvestorOnboardingCase::STATUS_DRAFT => $index % 2,
            InvestorOnboardingCase::STATUS_REJECTED, InvestorOnboardingCase::STATUS_ACTION_REQUIRED => min(2, $documentTypes->count()),
            default => $documentTypes->count(),
        };

        foreach ($documentTypes->take($count) as $position => $type) {
            $documentStatus = match (true) {
                $case->status === InvestorOnboardingCase::STATUS_APPROVED => InvestorDocument::STATUS_ACCEPTED,
                $case->status === InvestorOnboardingCase::STATUS_REJECTED => InvestorDocument::STATUS_REJECTED,
                $index % 11 === 0 && $position === 0 => InvestorDocument::STATUS_EXPIRED,
                default => InvestorDocument::STATUS_QUARANTINED,
            };
            $scanStatus = $documentStatus === InvestorDocument::STATUS_ACCEPTED ? InvestorDocument::SCAN_CLEAN : InvestorDocument::SCAN_PENDING;
            $checksum = hash('sha256', "demo-investor-{$index}-{$type->code}");
            $document = InvestorDocument::query()->updateOrCreate([
                'investor_onboarding_case_id' => $case->id,
                'document_type_id' => $type->id,
            ], [
                'investor_profile_id' => $case->investor_profile_id,
                'status' => $documentStatus,
                'issued_at' => now()->subYear()->subDays($index),
                'expires_at' => $type->requires_expiry ? ($documentStatus === InvestorDocument::STATUS_EXPIRED ? now()->subMonth() : now()->addYears(3)) : null,
                'verified_at' => $documentStatus !== InvestorDocument::STATUS_QUARANTINED ? now()->subDays($index % 10) : null,
                'verified_by' => $documentStatus !== InvestorDocument::STATUS_QUARANTINED ? $reviewer?->id : null,
                'rejection_reason' => $documentStatus === InvestorDocument::STATUS_REJECTED ? 'Image quality or document details require correction.' : null,
                'checksum_sha256' => $checksum,
                'malware_scan_status' => $scanStatus,
                'malware_scanned_at' => $scanStatus === InvestorDocument::SCAN_CLEAN ? now()->subDays($index % 10) : null,
            ]);

            if (! $document->hasMedia(InvestorDocument::COLLECTION_FILE)) {
                $document->addMediaFromString($this->samplePdf($case->reference, $type->name))
                    ->usingFileName("{$case->reference}-{$type->code}.pdf")
                    ->toMediaCollection(InvestorDocument::COLLECTION_FILE);
            }
        }
    }

    private function samplePdf(string $reference, string $type): string
    {
        $text = str_replace(['(', ')'], '', "Prototype KYC evidence: {$reference} - {$type}");

        return "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Contents 4 0 R>>endobj\n4 0 obj<</Length 70>>stream\nBT /F1 12 Tf 72 720 Td ({$text}) Tj ET\nendstream\nendobj\ntrailer<</Root 1 0 R>>\n%%EOF";
    }
}
