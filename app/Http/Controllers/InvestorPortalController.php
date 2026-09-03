<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvestorDocumentUploadRequest;
use App\Http\Requests\InvestorMatchPreferenceRequest;
use App\Http\Requests\InvestorProfileRequest;
use App\Models\InvestorDocument;
use App\Models\InvestorDocumentType;
use App\Models\InvestorOnboardingCase;
use App\Models\InvestorProfile;
use App\Models\Region;
use App\Models\Sector;
use App\Models\User;
use App\Services\InvestorOnboardingService;
use App\Services\InvestorOpportunityMatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvestorPortalController extends Controller
{
    public function show(Request $request, InvestorOpportunityMatcher $matcher): View
    {
        $this->assertInvestor($request->user());
        $profile = $this->profile($request->user());
        $case = $profile->onboardingCases()
            ->with([
                'documents' => fn ($query) => $query->with(['type:id,code,name,requires_expiry', 'media'])->latest(),
                'events' => fn ($query) => $query->with('actor:id,name')->latest('occurred_at'),
            ])
            ->latest('id')
            ->first();
        $preference = $profile->matchPreference()->with(['sectors:id', 'regions:id'])->first();
        $matches = $preference ? $matcher->matches($preference) : collect();
        $matchSectors = $matches
            ->groupBy(fn (array $match) => $match['opportunity']->sector->name)
            ->map->count()
            ->sortDesc();
        $evidenceStatuses = collect(['accepted', 'pending', 'rejected'])
            ->mapWithKeys(fn (string $status) => [$status => $case?->documents->where('status', $status)->count() ?? 0]);

        return view('portal.investor', [
            'profile' => $profile,
            'case' => $case,
            'matchPreference' => $preference,
            'matches' => $matches,
            'matchSectorChart' => ['type' => 'bar', 'labels' => $matchSectors->keys(), 'datasets' => [['label' => 'Matched opportunities', 'data' => $matchSectors->values()]]],
            'evidenceChart' => ['type' => 'doughnut', 'labels' => $evidenceStatuses->keys()->map(fn (string $status) => str($status)->title()), 'datasets' => [['label' => 'Documents', 'data' => $evidenceStatuses->values()]]],
            'sectors' => Sector::query()->select(['id', 'name'])->orderBy('name')->get(),
            'regions' => Region::query()->select(['id', 'name'])->orderBy('name')->get(),
            'inquiryCount' => $profile->user->investorInquiries()->count(),
            'documentTypes' => InvestorDocumentType::query()
                ->where('is_active', true)
                ->where(fn ($query) => $query->whereNull('applies_to_profile_type')->orWhere('applies_to_profile_type', $profile->profile_type))
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function updateMatchPreferences(InvestorMatchPreferenceRequest $request): RedirectResponse
    {
        $profile = $this->profile($request->user());
        $data = $request->safe()->except(['sector_ids', 'region_ids']);

        DB::transaction(function () use ($profile, $data, $request): void {
            $preference = $profile->matchPreference()->updateOrCreate([], $data);
            $preference->sectors()->sync($request->validated('sector_ids'));
            $preference->regions()->sync($request->validated('region_ids'));
        });

        return to_route('investor.dashboard')->with('status', 'Investment preferences updated. Your matches have been refreshed.');
    }

    public function updateProfile(InvestorProfileRequest $request): RedirectResponse
    {
        $profile = $this->profile($request->user());
        $data = $request->validated();
        $data['country_code'] = Str::upper($data['country_code']);
        $data['nationality_country_code'] = isset($data['nationality_country_code']) ? Str::upper($data['nationality_country_code']) : null;
        $profile->update($data + ['updated_by' => $request->user()->id, 'version' => $profile->version + 1]);

        return to_route('investor.dashboard')->with('status', 'Investor profile updated.');
    }

    public function start(Request $request, InvestorOnboardingService $workflow): RedirectResponse
    {
        $this->assertInvestor($request->user());
        $workflow->createDraft($this->profile($request->user()), $request->user());

        return to_route('investor.dashboard')->with('status', 'Onboarding case started.');
    }

    public function upload(InvestorDocumentUploadRequest $request, InvestorOnboardingCase $case): RedirectResponse
    {
        $this->assertEditableOwner($case, $request->user());
        $type = InvestorDocumentType::query()->where('code', $request->validated('document_type'))->firstOrFail();
        if ($type->applies_to_profile_type && $type->applies_to_profile_type !== $case->profile->profile_type) {
            throw ValidationException::withMessages(['document_type' => 'This document type does not apply to the selected profile type.']);
        }
        if ($type->requires_expiry && ! $request->validated('expires_at')) {
            throw ValidationException::withMessages(['expires_at' => 'An expiry date is required for this document type.']);
        }

        $upload = $request->file('document');
        $document = DB::transaction(fn () => $case->documents()->create([
            'investor_profile_id' => $case->investor_profile_id,
            'document_type_id' => $type->id,
            'issued_at' => $request->validated('issued_at'),
            'expires_at' => $request->validated('expires_at'),
            'checksum_sha256' => hash_file('sha256', $upload->getRealPath()),
        ]));

        try {
            $document->addMedia($upload)
                ->usingFileName(Str::uuid().'.'.$upload->guessExtension())
                ->toMediaCollection(InvestorDocument::COLLECTION_FILE);
        } catch (\Throwable $exception) {
            $document->delete();
            throw $exception;
        }

        return to_route('investor.dashboard')->with('status', 'Document uploaded to quarantine for security review.');
    }

    public function submit(Request $request, InvestorOnboardingCase $case, InvestorOnboardingService $workflow): RedirectResponse
    {
        $this->assertEditableOwner($case, $request->user());
        $workflow->submit($case, $request->user());

        return to_route('investor.dashboard')->with('status', 'Onboarding case submitted for review.');
    }

    public function download(Request $request, InvestorDocument $document): StreamedResponse
    {
        Gate::authorize('view', $document);
        $media = $document->getFirstMedia(InvestorDocument::COLLECTION_FILE);
        abort_unless($media, 404);

        return Storage::disk($media->disk)->download($media->getPathRelativeToRoot(), $document->type->code.'.'.$media->extension);
    }

    private function profile(User $user): InvestorProfile
    {
        return $user->investorProfile()->firstOrCreate([], [
            'display_name' => $user->name,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function assertInvestor(User $user): void
    {
        abort_unless($user->account_type === User::ACCOUNT_INVESTOR && $user->isActive(), 403);
    }

    private function assertEditableOwner(InvestorOnboardingCase $case, User $user): void
    {
        $this->assertInvestor($user);
        abort_unless($case->profile->user_id === $user->id, 403);
        abort_unless(in_array($case->status, [InvestorOnboardingCase::STATUS_DRAFT, InvestorOnboardingCase::STATUS_ACTION_REQUIRED], true), 403);
    }
}
