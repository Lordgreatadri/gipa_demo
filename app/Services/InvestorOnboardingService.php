<?php

namespace App\Services;

use App\Models\InvestorDocument;
use App\Models\InvestorDocumentType;
use App\Models\InvestorOnboardingCase;
use App\Models\InvestorProfile;
use App\Models\User;
use App\Notifications\WorkflowTransitionNotification;
use App\Support\InvestorPermissions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvestorOnboardingService
{
    public function createDraft(InvestorProfile $profile, User $actor): InvestorOnboardingCase
    {
        $this->authorizeOwner($profile, $actor);

        return DB::transaction(function () use ($profile, $actor): InvestorOnboardingCase {
            $profile = InvestorProfile::query()->lockForUpdate()->findOrFail($profile->id);
            $existing = $profile->onboardingCases()->whereIn('status', [
                InvestorOnboardingCase::STATUS_DRAFT,
                InvestorOnboardingCase::STATUS_SUBMITTED,
                InvestorOnboardingCase::STATUS_UNDER_REVIEW,
                InvestorOnboardingCase::STATUS_ACTION_REQUIRED,
            ])->latest('id')->first();

            if ($existing) {
                return $existing;
            }

            $case = $profile->onboardingCases()->create([
                'reference' => 'GIPA-'.now()->format('Y').'-'.Str::upper(Str::random(8)),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
            $case->events()->create([
                'actor_id' => $actor->id,
                'action' => 'created',
                'to_status' => InvestorOnboardingCase::STATUS_DRAFT,
                'metadata' => ['schema_version' => 1],
            ]);
            $profile->forceFill(['onboarding_state' => InvestorProfile::ONBOARDING_IN_PROGRESS])->save();

            return $case->fresh();
        });
    }

    public function submit(InvestorOnboardingCase $case, User $actor): InvestorOnboardingCase
    {
        $this->authorizeOwner($case->profile, $actor);
        $expected = $case->status === InvestorOnboardingCase::STATUS_ACTION_REQUIRED
            ? InvestorOnboardingCase::STATUS_ACTION_REQUIRED
            : InvestorOnboardingCase::STATUS_DRAFT;

        return $this->transition($case, $actor, 'submit', $expected, InvestorOnboardingCase::STATUS_SUBMITTED, function (InvestorOnboardingCase $locked): array {
            $missing = $this->missingRequiredDocumentTypes($locked, false);
            if ($missing->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'documents' => 'Upload all required evidence before submission: '.$missing->pluck('name')->join(', ').'.',
                ]);
            }

            return [
                'submitted_at' => now(),
                'sla_due_at' => now()->addHours(config('iomp.workflow.investor_onboarding_review_hours')),
                'decision_reason' => null,
            ];
        });
    }

    public function startReview(InvestorOnboardingCase $case, User $actor): InvestorOnboardingCase
    {
        Gate::forUser($actor)->authorize(InvestorPermissions::REVIEW);

        return $this->transition($case, $actor, 'start_review', InvestorOnboardingCase::STATUS_SUBMITTED, InvestorOnboardingCase::STATUS_UNDER_REVIEW, fn () => [
            'assigned_to' => $actor->id,
            'review_started_at' => now(),
        ]);
    }

    public function requestChanges(InvestorOnboardingCase $case, User $actor, string $reason): InvestorOnboardingCase
    {
        Gate::forUser($actor)->authorize(InvestorPermissions::REVIEW);

        return $this->transition($case, $actor, 'request_changes', InvestorOnboardingCase::STATUS_UNDER_REVIEW, InvestorOnboardingCase::STATUS_ACTION_REQUIRED, fn () => [
            'decision_reason' => $reason,
            'sla_due_at' => null,
        ], $reason);
    }

    public function approve(InvestorOnboardingCase $case, User $actor, ?string $reason = null): InvestorOnboardingCase
    {
        Gate::forUser($actor)->authorize(InvestorPermissions::REVIEW);

        return $this->transition($case, $actor, 'approve', InvestorOnboardingCase::STATUS_UNDER_REVIEW, InvestorOnboardingCase::STATUS_APPROVED, function (InvestorOnboardingCase $locked) use ($reason): array {
            $missing = $this->missingRequiredDocumentTypes($locked, true);
            if ($missing->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'documents' => 'Required evidence must pass security and KYC review before approval: '.$missing->pluck('name')->join(', ').'.',
                ]);
            }

            return [
                'decided_at' => now(),
                'decision_reason' => $reason,
                'sla_due_at' => null,
            ];
        }, $reason);
    }

    public function reject(InvestorOnboardingCase $case, User $actor, string $reason): InvestorOnboardingCase
    {
        Gate::forUser($actor)->authorize(InvestorPermissions::REVIEW);

        return $this->transition($case, $actor, 'reject', InvestorOnboardingCase::STATUS_UNDER_REVIEW, InvestorOnboardingCase::STATUS_REJECTED, fn () => [
            'decided_at' => now(),
            'decision_reason' => $reason,
            'sla_due_at' => null,
        ], $reason);
    }

    public function acceptDocument(InvestorDocument $document, User $actor): InvestorDocument
    {
        Gate::forUser($actor)->authorize(InvestorPermissions::COMPLIANCE_MANAGE);

        if ($document->malware_scan_status !== InvestorDocument::SCAN_CLEAN || ! $document->hasMedia(InvestorDocument::COLLECTION_FILE)) {
            throw ValidationException::withMessages(['document' => 'Only clean, stored documents may be accepted.']);
        }

        $document->forceFill([
            'status' => InvestorDocument::STATUS_ACCEPTED,
            'verified_at' => now(),
            'verified_by' => $actor->id,
            'rejection_reason' => null,
        ])->save();

        return $document->fresh();
    }

    public function recordCleanScanAndAccept(InvestorDocument $document, User $actor): InvestorDocument
    {
        Gate::forUser($actor)->authorize(InvestorPermissions::COMPLIANCE_MANAGE);
        if (! $document->hasMedia(InvestorDocument::COLLECTION_FILE)) {
            throw ValidationException::withMessages(['document' => 'The private document file is missing.']);
        }

        $document->forceFill([
            'malware_scan_status' => InvestorDocument::SCAN_CLEAN,
            'malware_scanned_at' => now(),
        ])->save();

        return $this->acceptDocument($document, $actor);
    }

    public function rejectDocument(InvestorDocument $document, User $actor, string $reason): InvestorDocument
    {
        Gate::forUser($actor)->authorize(InvestorPermissions::COMPLIANCE_MANAGE);
        $document->forceFill([
            'status' => InvestorDocument::STATUS_REJECTED,
            'verified_at' => now(),
            'verified_by' => $actor->id,
            'rejection_reason' => $reason,
        ])->save();

        return $document->fresh();
    }

    private function transition(InvestorOnboardingCase $case, User $actor, string $action, string $expected, string $next, callable $changes, ?string $reason = null): InvestorOnboardingCase
    {
        [$case, $recipient] = DB::transaction(function () use ($case, $actor, $action, $expected, $next, $changes, $reason): array {
            $case = InvestorOnboardingCase::query()->lockForUpdate()->findOrFail($case->id);
            if ($case->status !== $expected) {
                throw ValidationException::withMessages(['workflow' => "Onboarding case must be {$expected} for this action."]);
            }

            $before = $case->only(['status', 'assigned_to', 'sla_due_at', 'submitted_at', 'decided_at', 'version']);
            $case->forceFill([
                ...$changes($case),
                'status' => $next,
                'updated_by' => $actor->id,
                'version' => $case->version + 1,
            ])->save();
            $event = $case->events()->create([
                'actor_id' => $actor->id,
                'action' => $action,
                'from_status' => $expected,
                'to_status' => $next,
                'reason' => $reason,
                'metadata' => ['schema_version' => 1],
            ]);
            $profileState = match ($next) {
                InvestorOnboardingCase::STATUS_SUBMITTED, InvestorOnboardingCase::STATUS_UNDER_REVIEW => InvestorProfile::ONBOARDING_SUBMITTED,
                InvestorOnboardingCase::STATUS_ACTION_REQUIRED => InvestorProfile::ONBOARDING_ACTION_REQUIRED,
                InvestorOnboardingCase::STATUS_APPROVED => InvestorProfile::ONBOARDING_VERIFIED,
                default => InvestorProfile::ONBOARDING_IN_PROGRESS,
            };
            $case->profile->forceFill([
                'onboarding_state' => $profileState,
                'onboarded_at' => $next === InvestorOnboardingCase::STATUS_APPROVED ? now() : $case->profile->onboarded_at,
                'updated_by' => $actor->id,
                'version' => $case->profile->version + 1,
            ])->save();
            $this->audit($case, $actor, $action, $before, $case->only(array_keys($before)), $event);

            return [$case->fresh(), $case->profile->user];
        });

        if (! $recipient->is($actor)) {
            Notification::send($recipient, new WorkflowTransitionNotification('investor onboarding', $case->reference, $action, $case->status, $reason));
        }

        return $case;
    }

    private function missingRequiredDocumentTypes(InvestorOnboardingCase $case, bool $requireAcceptance)
    {
        return InvestorDocumentType::query()
            ->where('is_active', true)
            ->where('is_required', true)
            ->where(fn ($query) => $query->whereNull('applies_to_profile_type')->orWhere('applies_to_profile_type', $case->profile->profile_type))
            ->whereDoesntHave('documents', fn ($query) => $query
                ->where('investor_onboarding_case_id', $case->id)
                ->when($requireAcceptance, fn ($documents) => $documents
                    ->where('status', InvestorDocument::STATUS_ACCEPTED)
                    ->where('malware_scan_status', InvestorDocument::SCAN_CLEAN))
                ->whereHas('media', fn ($media) => $media->where('collection_name', InvestorDocument::COLLECTION_FILE)))
            ->orderBy('sort_order')
            ->get(['id', 'name']);
    }

    private function authorizeOwner(InvestorProfile $profile, User $actor): void
    {
        if (! $profile->user->is($actor) || ! $actor->isActive() || $actor->account_type !== User::ACCOUNT_INVESTOR) {
            throw new AuthorizationException('This investor profile is not accessible.');
        }
    }

    private function audit(InvestorOnboardingCase $case, User $actor, string $action, array $before, array $after, Model $event): void
    {
        activity('workflow')
            ->causedBy($actor)
            ->performedOn($case)
            ->event($action)
            ->withProperties(['before' => $before, 'after' => $after, 'workflow_event_uuid' => $event->uuid])
            ->log("Investor onboarding {$action}");
    }
}