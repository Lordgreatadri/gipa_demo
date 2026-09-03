<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InvestorOnboardingTransitionRequest;
use App\Models\InvestorDocument;
use App\Models\InvestorOnboardingCase;
use App\Services\InvestorOnboardingService;
use App\Support\InvestorPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvestorOnboardingController extends Controller
{
    public function index(Request $request): View
    {
        return $this->workspace($request, false, true);
    }

    public function overview(Request $request): View
    {
        return $this->workspace($request, true, false);
    }

    private function workspace(Request $request, bool $showOverview, bool $showList): View
    {
        abort_unless($request->user()->can(InvestorPermissions::VIEW), 403);
        $metrics = InvestorOnboardingCase::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as submitted', [InvestorOnboardingCase::STATUS_SUBMITTED])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as reviewing', [InvestorOnboardingCase::STATUS_UNDER_REVIEW])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved', [InvestorOnboardingCase::STATUS_APPROVED])
            ->selectRaw('SUM(CASE WHEN status IN (?, ?) AND sla_due_at < ? THEN 1 ELSE 0 END) as overdue', [InvestorOnboardingCase::STATUS_SUBMITTED, InvestorOnboardingCase::STATUS_UNDER_REVIEW, now()])
            ->first();

        return view('admin.investors.index', [
            'showOverview' => $showOverview,
            'showList' => $showList,
            'metrics' => $metrics,
            'cases' => InvestorOnboardingCase::query()
                ->select('id', 'uuid', 'reference', 'investor_profile_id', 'assigned_to', 'status', 'sla_due_at', 'updated_at')
                ->with(['profile:id,user_id,display_name,profile_type', 'profile.user:id,email', 'assignee:id,name'])
                ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
                ->orderByRaw('CASE WHEN sla_due_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('sla_due_at')
                ->cursorPaginate(20)
                ->withQueryString(),
        ]);
    }

    public function show(Request $request, InvestorOnboardingCase $case): View
    {
        abort_unless($request->user()->can(InvestorPermissions::VIEW), 403);
        $case->load([
            'profile.user:id,uuid,name,email,phone,organization',
            'assignee:id,name',
            'documents' => fn ($query) => $query->with(['type:id,code,name,requires_expiry', 'verifier:id,name', 'media'])->latest(),
            'events' => fn ($query) => $query->with('actor:id,name')->latest('occurred_at'),
        ]);

        return view('admin.investors.show', ['case' => $case]);
    }

    public function transition(InvestorOnboardingTransitionRequest $request, InvestorOnboardingCase $case, string $action, InvestorOnboardingService $workflow): RedirectResponse
    {
        $reason = $request->validated('reason');
        $case = match ($action) {
            'start-review' => $workflow->startReview($case, $request->user()),
            'request-changes' => $workflow->requestChanges($case, $request->user(), $reason),
            'approve' => $workflow->approve($case, $request->user(), $reason),
            'reject' => $workflow->reject($case, $request->user(), $reason),
            default => throw ValidationException::withMessages(['workflow' => 'Unknown onboarding workflow action.']),
        };

        return to_route('staff.investors.show', $case)->with('status', 'Onboarding workflow updated.');
    }

    public function documentDecision(InvestorOnboardingTransitionRequest $request, InvestorDocument $document, string $action, InvestorOnboardingService $workflow): RedirectResponse
    {
        match ($action) {
            'accept' => $workflow->recordCleanScanAndAccept($document, $request->user()),
            'reject' => $workflow->rejectDocument($document, $request->user(), $request->validated('reason') ?? 'Document rejected during review.'),
            default => throw ValidationException::withMessages(['document' => 'Unknown document decision.']),
        };

        return to_route('staff.investors.show', $document->onboardingCase)->with('status', 'KYC document decision recorded.');
    }
}
